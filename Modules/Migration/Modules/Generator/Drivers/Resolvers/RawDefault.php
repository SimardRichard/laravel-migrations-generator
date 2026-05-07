<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers;

/**
 * Marqueur immuable pour une valeur par défaut SQL non scalaire.
 *
 * Utilisé par le ColumnRenderer pour générer `DB::raw(...)` au lieu d'un
 * littéral PHP (ex : CURRENT_TIMESTAMP, nextval('...'), uuid_generate_v4()).
 */
final readonly class RawDefault
{
    public function __construct(private string $expression) {}

    public function expression(): string
    {
        return $this->expression;
    }
}
