<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

/**
 * Options agrégées passées par la CLI à l'orchestrateur Generator.
 *
 * Liste blanche `tables` ; liste noire `ignored`. La date sert à préfixer
 * les fichiers de migration (`YYYY_MM_DD_HHMMSS_...`).
 */
final readonly class GenerateOptions
{
    /**
     * @param  string[]  $tables
     * @param  string[]  $ignored
     */
    public function __construct(
        public string $connection,
        public array $tables,
        public array $ignored,
        public string $path,
        public ?string $stubPath,
        public bool $force,
        public string $date,
    ) {}
}
