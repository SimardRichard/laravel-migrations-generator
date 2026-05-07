<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Extract\Providers\ExtractServiceProvider;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Providers\GeneratorServiceProvider;
use App\Modules\Migration\Modules\Migration\Modules\Import\Providers\ImportServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Niveau 2 — sous-module core Migration.
 *
 * Enregistre les bindings d'interfaces partagées et les ServiceProviders
 * des sous-sous-modules Generator/Extract/Import selon la config.
 */
final class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/migration-core.php', 'migration-core');

        $submodules = (array) config('migration-core.submodules', []);

        if (($submodules['generator'] ?? false) === true) {
            $this->app->register(GeneratorServiceProvider::class);
        }

        if (($submodules['extract'] ?? false) === true) {
            $this->app->register(ExtractServiceProvider::class);
        }

        if (($submodules['import'] ?? false) === true) {
            $this->app->register(ImportServiceProvider::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/migration-core.php' => config_path('migration-core.php'),
            ], 'migration-core-config');
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'migration');
    }
}
