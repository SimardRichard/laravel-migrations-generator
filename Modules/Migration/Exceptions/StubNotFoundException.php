<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class StubNotFoundException extends GenerationException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct(sprintf('Stub file not found: "%s".', $path));
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
