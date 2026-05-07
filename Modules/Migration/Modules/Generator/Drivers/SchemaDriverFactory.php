<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers;

use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;

/**
 * Construit le SchemaDriver adapté au moteur d'une connexion configurée.
 *
 * Plan 1 ne supporte que SQLite. Les drivers MySQL/Postgres/MSSQL/Oracle
 * arrivent dans les plans suivants — ce match exhaustif lèvera
 * UnsupportedDriverException tant qu'ils n'ont pas été ajoutés.
 */
final class SchemaDriverFactory
{
    public function __construct(private readonly Application $app) {}

    public function forConnection(string $name): SchemaDriver
    {
        $config = $this->config()->get("database.connections.$name");
        if (! is_array($config)) {
            throw InvalidConnectionException::for($name);
        }

        $driverName = is_string($config['driver'] ?? null) ? $config['driver'] : '';

        // Vérifie le support AVANT d'ouvrir la connexion : DatabaseManager
        // lèverait InvalidArgumentException pour un driver inconnu (ex: oracle)
        // avant qu'on ait pu lever notre UnsupportedDriverException.
        if ($driverName !== 'sqlite') {
            throw UnsupportedDriverException::for($driverName);
        }

        return new SqliteDriver(
            $this->databaseManager()->connection($name),
            new ColumnTypeResolver,
            new DefaultValueResolver,
        );
    }

    private function config(): Repository
    {
        /** @var Repository $repo */
        $repo = $this->app->make('config');

        return $repo;
    }

    private function databaseManager(): DatabaseManager
    {
        /** @var DatabaseManager $db */
        $db = $this->app->make('db');

        return $db;
    }
}
