<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Exceptions;

use RuntimeException;

/**
 * Racine de toute exception levée par le module Migration.
 *
 * Hiérarchie en 4 branches : Configuration, SchemaRead, Generation, Write.
 * Les sous-classes peuvent enrichir le message via {@see context()}.
 */
abstract class MigrationCoreException extends RuntimeException
{
    /** @return array<string, mixed> */
    public function context(): array
    {
        return [];
    }
}
