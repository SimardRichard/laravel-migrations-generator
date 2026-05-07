<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Writers;

use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteFailedException;
use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use Illuminate\Filesystem\Filesystem;

/**
 * Écriture des MigrationFile sur le filesystem local.
 *
 * Crée le répertoire cible si absent. Refuse l'écrasement sauf si
 * `$force = true`. Wrappe les échecs d'I/O dans WriteFailedException pour
 * un reporting cohérent.
 */
final class FilesystemMigrationWriter implements MigrationWriter
{
    public function __construct(private readonly Filesystem $files) {}

    public function write(MigrationFile $file, string $directory, bool $force): string
    {
        $path = rtrim($directory, '/').'/'.$file->filename;

        if ($this->files->exists($path) && ! $force) {
            throw FileAlreadyExistsException::for($path);
        }

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, recursive: true);
        }

        if ($this->files->put($path, $file->contents) === false) {
            throw WriteFailedException::for($path, 'Filesystem::put returned false');
        }

        return $path;
    }
}
