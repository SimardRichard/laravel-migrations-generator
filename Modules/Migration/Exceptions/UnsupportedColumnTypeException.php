<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class UnsupportedColumnTypeException extends GenerationException
{
    public function __construct(
        public readonly string $table,
        public readonly string $column,
        public readonly string $type,
        public readonly string $driver,
    ) {
        parent::__construct(sprintf('Unsupported column type "%s" on %s.%s for driver "%s".', $type, $table, $column, $driver));
    }

    public static function for(string $table, string $column, string $type, string $driver): self
    {
        return new self($table, $column, $type, $driver);
    }

    public function context(): array
    {
        return ['table' => $this->table, 'column' => $this->column, 'type' => $this->type, 'driver' => $this->driver];
    }
}
