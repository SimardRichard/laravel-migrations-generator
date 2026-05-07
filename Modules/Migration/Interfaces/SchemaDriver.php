<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;

/**
 * Contrat d'un driver d'introspection de schéma SQL.
 *
 * Une implémentation par moteur (SQLite, MySQL, PostgreSQL, MSSQL, Oracle)
 * vit dans `Modules/Generator/Drivers/`. Les méthodes lèvent
 * SchemaReadException si la lecture échoue.
 */
interface SchemaDriver
{
    /** @return string[] */
    public function getTables(): array;

    /** @return ColumnSchema[] */
    public function getColumns(string $table): array;

    /** @return IndexSchema[] */
    public function getIndexes(string $table): array;

    /** @return ForeignKeySchema[] */
    public function getForeignKeys(string $table): array;

    public function name(): string;
}
