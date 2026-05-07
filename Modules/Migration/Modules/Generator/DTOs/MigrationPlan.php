<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs;

/**
 * Liste ordonnée des fichiers de migration à écrire.
 *
 * L'ordre des `files` est l'ordre d'écriture définitif (déjà topologiquement
 * trié par `BuildMigrationPlanAction`).
 */
final readonly class MigrationPlan
{
    /** @param MigrationFile[] $files */
    public function __construct(public array $files) {}

    public function isEmpty(): bool
    {
        return $this->files === [];
    }
}
