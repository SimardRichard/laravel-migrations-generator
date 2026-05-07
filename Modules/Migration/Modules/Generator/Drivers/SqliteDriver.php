<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

/**
 * Driver SQLite : introspection via les pragmas natifs.
 *
 * Filtre les tables système (`sqlite_*`) et les index auto-générés
 * (`sqlite_autoindex_*`). Les FK composites sont regroupées par leur `id`
 * SQLite.
 */
final class SqliteDriver extends AbstractSchemaDriver
{
    public function name(): string
    {
        return 'sqlite';
    }

    public function getTables(): array
    {
        $rows = $this->connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
        );

        return array_map(fn (object $r): string => (string) ((array) $r)['name'], $rows);
    }

    public function getColumns(string $table): array
    {
        $rows = $this->connection->select("PRAGMA table_info($table)");
        $columns = [];

        foreach ($rows as $row) {
            $type = $this->columnTypeResolver->forSqlite((string) $row->type);
            [$length, $precision, $scale] = $this->extractTypeMetrics((string) $row->type);

            $columns[] = new ColumnSchema(
                name: (string) $row->name,
                type: $type,
                length: $length,
                precision: $precision,
                scale: $scale,
                nullable: ((int) $row->notnull) === 0 && (int) $row->pk === 0,
                unsigned: (int) $row->pk === 1,
                autoIncrement: (int) $row->pk === 1 && stripos((string) $row->type, 'int') !== false,
                default: $this->defaultValueResolver->resolve($row->dflt_value === null ? null : (string) $row->dflt_value),
                comment: null,
                collation: null,
                generatedAs: null,
            );
        }

        return $columns;
    }

    public function getIndexes(string $table): array
    {
        $rows = $this->connection->select("PRAGMA index_list($table)");
        $indexes = [];

        foreach ($rows as $row) {
            if (str_starts_with((string) $row->name, 'sqlite_autoindex_')) {
                continue;
            }

            $cols = $this->connection->select("PRAGMA index_info({$row->name})");
            $columnNames = array_map(fn (object $c): string => (string) ((array) $c)['name'], $cols);

            $indexes[] = new IndexSchema(
                name: (string) $row->name,
                type: ((int) $row->unique) === 1 ? IndexType::Unique : IndexType::Index,
                columns: $columnNames,
                isComposite: count($columnNames) > 1,
                algorithm: null,
                where: null,
            );
        }

        return $indexes;
    }

    public function getForeignKeys(string $table): array
    {
        $rows = $this->connection->select("PRAGMA foreign_key_list($table)");
        /** @var array<int, array{table:string, columns:list<string>, referenced:list<string>, on_update:OnAction, on_delete:OnAction}> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $grouped[$id] ??= [
                'table' => (string) $row->table,
                'columns' => [],
                'referenced' => [],
                'on_update' => OnAction::fromSql((string) $row->on_update),
                'on_delete' => OnAction::fromSql((string) $row->on_delete),
            ];
            $grouped[$id]['columns'][] = (string) $row->from;
            $grouped[$id]['referenced'][] = (string) $row->to;
        }

        $result = [];
        foreach ($grouped as $id => $g) {
            $result[] = new ForeignKeySchema(
                name: sprintf('%s_fk_%d', $table, $id),
                columns: $g['columns'],
                referencedTable: $g['table'],
                referencedColumns: $g['referenced'],
                onUpdate: $g['on_update'],
                onDelete: $g['on_delete'],
            );
        }

        return $result;
    }

    /** @return array{0:int|null, 1:int|null, 2:int|null} */
    private function extractTypeMetrics(string $native): array
    {
        if (preg_match('/\((\d+),\s*(\d+)\)/', $native, $m) === 1) {
            return [null, (int) $m[1], (int) $m[2]];
        }
        if (preg_match('/\((\d+)\)/', $native, $m) === 1) {
            return [(int) $m[1], null, null];
        }

        return [null, null, null];
    }
}
