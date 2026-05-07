<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class UnsupportedDriverException extends ConfigurationException
{
    public function __construct(public readonly string $driver)
    {
        parent::__construct(sprintf('Unsupported database driver: "%s".', $driver));
    }

    public static function for(string $driver): self
    {
        return new self($driver);
    }

    public function context(): array
    {
        return ['driver' => $this->driver];
    }
}
