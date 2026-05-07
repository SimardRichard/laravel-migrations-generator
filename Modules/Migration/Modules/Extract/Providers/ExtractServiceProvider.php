<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Extract\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Extract\Console\Commands\ExtractCommand;
use Illuminate\Support\ServiceProvider;

final class ExtractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/extract.php', 'migration.extract');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExtractCommand::class]);

            $this->publishes([
                __DIR__.'/../Config/extract.php' => config_path('migration/extract.php'),
            ], 'migration-extract-config');
        }
    }
}
