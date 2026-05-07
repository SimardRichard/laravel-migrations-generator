<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use Illuminate\Database\Connection;

/**
 * Squelette commun aux drivers SQL : injecte la connexion et les deux
 * resolvers (type natif → ColumnType, valeur par défaut brute → PHP).
 */
abstract class AbstractSchemaDriver implements SchemaDriver
{
    public function __construct(
        protected readonly Connection $connection,
        protected readonly ColumnTypeResolver $columnTypeResolver,
        protected readonly DefaultValueResolver $defaultValueResolver,
    ) {}
}
