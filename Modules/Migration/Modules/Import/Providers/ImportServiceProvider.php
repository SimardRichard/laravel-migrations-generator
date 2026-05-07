<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Import\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Import\Console\Commands\ImportCommand;
use Illuminate\Support\ServiceProvider;

final class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/import.php', 'migration.import');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ImportCommand::class]);

            $this->publishes([
                __DIR__.'/../Config/import.php' => config_path('migration/import.php'),
            ], 'migration-import-config');
        }
    }
}
