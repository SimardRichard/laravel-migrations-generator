<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\IndexType;

/**
 * Description immuable d'un index SQL (simple ou composite).
 */
final readonly class IndexSchema
{
    /** @param string[] $columns */
    public function __construct(
        public string $name,
        public IndexType $type,
        public array $columns,
        public bool $isComposite,
        public ?string $algorithm,
        public ?string $where,
    ) {}
}
