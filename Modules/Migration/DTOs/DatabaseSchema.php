<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

/**
 * Représentation immuable de la structure complète d'une base de données.
 */
final readonly class DatabaseSchema
{
    /** @param TableSchema[] $tables */
    public function __construct(
        public string $connection,
        public array $tables,
    ) {}

    public function table(string $name): ?TableSchema
    {
        foreach ($this->tables as $t) {
            if ($t->name === $name) {
                return $t;
            }
        }

        return null;
    }
}
