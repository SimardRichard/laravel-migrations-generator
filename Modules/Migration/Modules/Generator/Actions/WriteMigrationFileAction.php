<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Actions;

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

/**
 * Action 4 : itère sur le plan et délègue chaque fichier au MigrationWriter
 * configuré (Filesystem en Plan 1).
 *
 * Retourne la liste ordonnée des chemins écrits pour le reporting CLI.
 */
final readonly class WriteMigrationFileAction
{
    public function __construct(private MigrationWriter $writer) {}

    /** @return string[] */
    public function execute(MigrationPlan $plan, GenerateOptions $options): array
    {
        $paths = [];
        foreach ($plan->files as $file) {
            $paths[] = $this->writer->write($file, $options->path, $options->force);
        }

        return $paths;
    }
}
