<?php

declare(strict_types=1);

namespace App\Modules\Migration\Providers;

use App\Modules\Migration\Modules\Migration\Providers\MigrationServiceProvider as CoreMigrationServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Niveau 1 — wrapper module Migration.
 *
 * Enregistre transitivement le ServiceProvider du sous-module core, qui
 * lui-même enregistre les sous-sous-modules Generator/Extract/Import.
 */
final class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/migration.php', 'migration');

        if (! (bool) config('migration.enabled', true)) {
            return;
        }

        $this->app->register(CoreMigrationServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/migration.php' => config_path('migration.php'),
            ], 'migration-config');
        }
    }
}
