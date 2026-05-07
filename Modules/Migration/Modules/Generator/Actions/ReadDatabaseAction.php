<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

/**
 * Action 1 du pipeline Generator : convertit une connexion vers DatabaseSchema.
 *
 * - Liste blanche `tables` : si non vide, restreint aux tables nommées.
 * - Liste noire `ignored` : appliquée après la liste blanche.
 * - Stateless : aucune dépendance, sûr à instancier ad-hoc.
 */
final readonly class ReadDatabaseAction
{
    public function execute(SchemaDriver $driver, GenerateOptions $options): DatabaseSchema
    {
        $allTables = $driver->getTables();
        $selected = $this->select($allTables, $options);

        $tables = [];
        foreach ($selected as $name) {
            $tables[] = new TableSchema(
                name: $name,
                comment: null,
                charset: null,
                collation: null,
                columns: $driver->getColumns($name),
                indexes: $driver->getIndexes($name),
                foreignKeys: $driver->getForeignKeys($name),
            );
        }

        return new DatabaseSchema($options->connection, $tables);
    }

    /**
     * @param  string[]  $available
     * @return string[]
     */
    private function select(array $available, GenerateOptions $opts): array
    {
        $candidate = $opts->tables === [] ? $available : array_intersect($available, $opts->tables);

        return array_values(array_diff($candidate, $opts->ignored));
    }
}
