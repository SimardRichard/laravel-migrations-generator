<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class TableNotFoundException extends SchemaReadException
{
    public function __construct(public readonly string $table)
    {
        parent::__construct(sprintf('Table not found: "%s".', $table));
    }

    public static function for(string $table): self
    {
        return new self($table);
    }

    public function context(): array
    {
        return ['table' => $this->table];
    }
}
