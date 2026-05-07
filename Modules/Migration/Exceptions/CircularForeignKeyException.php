<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

final class CircularForeignKeyException extends GenerationException
{
    /** @param string[] $cycle */
    public function __construct(public readonly array $cycle)
    {
        parent::__construct('Circular foreign-key dependency detected: '.implode(' → ', $cycle));
    }

    /** @param string[] $cycle */
    public static function for(array $cycle): self
    {
        return new self($cycle);
    }

    public function context(): array
    {
        return ['cycle' => $this->cycle];
    }
}
