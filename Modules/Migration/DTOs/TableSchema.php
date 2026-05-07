<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

/**
 * Description immuable d'une table SQL avec ses colonnes, index et FK.
 */
final readonly class TableSchema
{
    /**
     * @param  ColumnSchema[]  $columns
     * @param  IndexSchema[]  $indexes
     * @param  ForeignKeySchema[]  $foreignKeys
     */
    public function __construct(
        public string $name,
        public ?string $comment,
        public ?string $charset,
        public ?string $collation,
        public array $columns,
        public array $indexes,
        public array $foreignKeys,
    ) {}
}
