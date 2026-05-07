<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;

/**
 * Contrat d'écriture d'un MigrationFile sur un support persistant.
 *
 * Implémentations possibles : Filesystem (Plan 1), in-memory (tests),
 * S3/Stockage cloud (futur).
 *
 * Retourne le chemin (ou identifiant) écrit pour permettre au CLI
 * d'afficher la liste des fichiers créés.
 */
interface MigrationWriter
{
    public function write(MigrationFile $file, string $directory, bool $force): string;
}
