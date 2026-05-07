<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

/**
 * Couple immuable (nom de fichier, contenu PHP) prêt à être écrit.
 */
final readonly class MigrationFile
{
    public function __construct(
        public string $filename,
        public string $contents,
    ) {}
}
