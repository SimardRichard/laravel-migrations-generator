<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class DatabaseAccessDeniedException extends SchemaReadException
{
    public function __construct(public readonly string $connection)
    {
        parent::__construct(sprintf('Access denied for connection: "%s".', $connection));
    }

    public static function for(string $connection): self
    {
        return new self($connection);
    }

    public function context(): array
    {
        return ['connection' => $this->connection];
    }
}
