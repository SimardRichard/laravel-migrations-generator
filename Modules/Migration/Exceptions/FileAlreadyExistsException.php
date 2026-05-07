<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class FileAlreadyExistsException extends WriteException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct(sprintf('Migration file already exists: "%s". Use --force to overwrite.', $path));
    }

    public static function for(string $path): self
    {
        return new self($path);
    }

    public function context(): array
    {
        return ['path' => $this->path];
    }
}
