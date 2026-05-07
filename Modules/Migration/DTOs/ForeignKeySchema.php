<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\OnAction;

/**
 * Description immuable d'une contrainte de clé étrangère.
 */
final readonly class ForeignKeySchema
{
    /**
     * @param  string[]  $columns
     * @param  string[]  $referencedColumns
     */
    public function __construct(
        public string $name,
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
        public OnAction $onUpdate,
        public OnAction $onDelete,
    ) {}
}
