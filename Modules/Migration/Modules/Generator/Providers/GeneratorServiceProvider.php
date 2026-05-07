<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Providers;

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands\GenerateCommand;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Writers\FilesystemMigrationWriter;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;

/**
 * Niveau 3 — sous-module Generator.
 *
 * Wire les contrats abstraits aux implémentations par défaut, et expose
 * la commande Artisan + les ressources publiables (stubs, config).
 */
final class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/generator.php', 'migration.generator');

        $this->app->bind(StubRenderer::class, function ($app): StubRenderer {
            /** @var Repository $config */
            $config = $app->make('config');
            $configured = $config->get('migration.generator.stubs_path', __DIR__.'/../stubs');
            $stubsPath = is_string($configured) ? $configured : __DIR__.'/../stubs';

            return new DefaultStubRenderer($stubsPath);
        });

        $this->app->bind(MigrationWriter::class, FilesystemMigrationWriter::class);
        $this->app->singleton(SchemaDriverFactory::class);
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
