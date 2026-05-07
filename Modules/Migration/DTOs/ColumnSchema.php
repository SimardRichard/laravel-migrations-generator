<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\DTOs;

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;

/**
 * Description immuable d'une colonne SQL telle que lue depuis un driver.
 *
 * Métadonnées exhaustives pour permettre au RenderEngine de reconstituer
 * la déclaration Laravel (`$table->...`) sans aller-retour vers le driver.
 */
final readonly class ColumnSchema
{
    public function __construct(
        public string $name,
        public ColumnType $type,
        public ?int $length,
        public ?int $precision,
        public ?int $scale,
        public bool $nullable,
        public bool $unsigned,
        public bool $autoIncrement,
        public mixed $default,
        public ?string $comment,
        public ?string $collation,
        public ?string $generatedAs,
    ) {}
}
