<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class WriteFailedException extends WriteException
{
    public function __construct(public readonly string $path, public readonly string $reason)
    {
        parent::__construct(sprintf('Could not write "%s": %s', $path, $reason));
    }

    public static function for(string $path, string $reason): self
    {
        return new self($path, $reason);
    }

    public function context(): array
    {
        return ['path' => $this->path, 'reason' => $this->reason];
    }
}
