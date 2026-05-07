<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Providers;

use App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands\GenerateCommand;
use Illuminate\Support\ServiceProvider;

final class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/generator.php', 'migration.generator');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([GenerateCommand::class]);

        $this->publishes([
            __DIR__.'/../stubs' => $this->app->basePath('stubs/migrations-generator'),
        ], 'migration-generator-stubs');

        $this->publishes([
            __DIR__.'/../Config/generator.php' => config_path('migration/generator.php'),
        ], 'migration-generator-config');
    }
}
