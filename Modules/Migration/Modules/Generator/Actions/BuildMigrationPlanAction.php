<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ColumnRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ForeignKeyRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\IndexRenderer;

/**
 * Action 2 du pipeline : transforme un DatabaseSchema en MigrationPlan.
 *
 * Stratégie en 2 fichiers :
 * 1. `*_create_tables.php` : CREATE TABLE pour toutes les tables, dans
 *    l'ordre topologique des dépendances FK (algorithme de Kahn).
 * 2. `*_add_foreign_keys.php` : contraintes FK ajoutées en deuxième passe.
 *
 * Ce découpage évite les cycles de définition tout en gardant les FK
 * cohérentes. Une FK auto-référentielle n'introduit pas d'arête (filtre).
 *
 * Le contenu est sérialisé avec un séparateur `BODY_SEPARATOR` qui sera
 * découpé par RenderMigrationAction pour injecter dans up/down du stub.
 */
final readonly class BuildMigrationPlanAction
{
    public const BODY_SEPARATOR = "\n/*###DOWN###*/\n";

    public function __construct(
        private ColumnRenderer $columnRenderer = new ColumnRenderer,
        private IndexRenderer $indexRenderer = new IndexRenderer,
        private ForeignKeyRenderer $foreignKeyRenderer = new ForeignKeyRenderer,
    ) {}

    public function execute(DatabaseSchema $schema, GenerateOptions $options): MigrationPlan
    {
        $orderedTables = $this->topologicalSort($schema->tables);

        $files = [];
        $files[] = $this->buildCreateTablesFile($orderedTables, $options, sequence: 0);

        if ($this->hasForeignKeys($orderedTables)) {
            $files[] = $this->buildForeignKeysFile($orderedTables, $options, sequence: 1);
        }

        return new MigrationPlan($files);
    }

    /**
     * Tri topologique de Kahn — O(V + E).
     *
     * Lève CircularForeignKeyException si un cycle est détecté.
     *
     * @param  TableSchema[]  $tables
     * @return TableSchema[]
     */
    private function topologicalSort(array $tables): array
    {
        $byName = [];
        foreach ($tables as $t) {
            $byName[$t->name] = $t;
        }

        /** @var array<string, list<string>> $deps */
        $deps = [];
        /** @var array<string, int> $inDegree */
        $inDegree = [];
        foreach ($tables as $t) {
            $deps[$t->name] = [];
            $inDegree[$t->name] = 0;
        }

        foreach ($tables as $t) {
            foreach ($t->foreignKeys as $fk) {
                if (isset($byName[$fk->referencedTable]) && $fk->referencedTable !== $t->name) {
                    $deps[$fk->referencedTable][] = $t->name;
                    $inDegree[$t->name]++;
                }
            }
        }

        $queue = [];
        foreach ($inDegree as $name => $degree) {
            if ($degree === 0) {
                $queue[] = $name;
            }
        }

        $sorted = [];
        while ($queue !== []) {
            $name = array_shift($queue);
            $sorted[] = $byName[$name];
            foreach ($deps[$name] as $dep) {
                if (--$inDegree[$dep] === 0) {
                    $queue[] = $dep;
                }
            }
        }

        if (count($sorted) !== count($tables)) {
            $remaining = array_values(array_filter(
                array_keys($inDegree),
                fn (string $n): bool => $inDegree[$n] > 0,
            ));
            throw CircularForeignKeyException::for($remaining);
        }

        return $sorted;
    }

    /** @param TableSchema[] $tables */
    private function hasForeignKeys(array $tables): bool
    {
        foreach ($tables as $t) {
            if ($t->foreignKeys !== []) {
                return true;
            }
        }

        return false;
    }

    /** @param TableSchema[] $tables */
    private function buildCreateTablesFile(array $tables, GenerateOptions $opts, int $sequence): MigrationFile
    {
        $up = '';
        $down = '';
        foreach ($tables as $t) {
            $up .= $this->renderCreateBlock($t)."\n\n";
            $down = "        Schema::dropIfExists('$t->name');\n".$down;
        }

        return new MigrationFile(
            filename: sprintf('%s_%02d_create_tables.php', $opts->date, $sequence),
            contents: rtrim($up).self::BODY_SEPARATOR.rtrim($down),
        );
    }

    /** @param TableSchema[] $tables */
    private function buildForeignKeysFile(array $tables, GenerateOptions $opts, int $sequence): MigrationFile
    {
        $up = '';
        $down = '';
        foreach ($tables as $t) {
            if ($t->foreignKeys === []) {
                continue;
            }
            $up .= "        Schema::table('$t->name', function (Blueprint \$table) {\n";
            $down .= "        Schema::table('$t->name', function (Blueprint \$table) {\n";
            foreach ($t->foreignKeys as $fk) {
                $up .= '            '.$this->foreignKeyRenderer->render($fk)."\n";
                $down .= "            \$table->dropForeign('$fk->name');\n";
            }
            $up .= "        });\n\n";
            $down .= "        });\n\n";
        }

        return new MigrationFile(
            filename: sprintf('%s_%02d_add_foreign_keys.php', $opts->date, $sequence),
            contents: rtrim($up).self::BODY_SEPARATOR.rtrim($down),
        );
    }

    private function renderCreateBlock(TableSchema $t): string
    {
        $body = "        Schema::create('$t->name', function (Blueprint \$table) {\n";
        foreach ($t->columns as $col) {
            $body .= '            '.$this->columnRenderer->render($col)."\n";
        }
        foreach ($t->indexes as $idx) {
            $line = $this->indexRenderer->render($idx);
            if ($line !== null) {
                $body .= '            '.$line."\n";
            }
        }
        $body .= '        });';

        return $body;
    }
}
