<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands;

use App\Modules\Migration\Modules\Migration\Exceptions\ConfigurationException;
use App\Modules\Migration\Modules\Migration\Exceptions\GenerationException;
use App\Modules\Migration\Modules\Migration\Exceptions\SchemaReadException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\ReadDatabaseAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\RenderMigrationAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\WriteMigrationFileAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use Illuminate\Console\Command;

/**
 * Commande Artisan principale du sous-module Generator.
 *
 * Orchestre les 4 actions du pipeline (Read → Build → Render → Write) et
 * mappe la hiérarchie d'exceptions sur des codes de sortie distincts pour
 * permettre aux scripts shell d'aiguiller selon le type d'erreur :
 *  1 = Configuration, 2 = SchemaRead, 3 = Generation, 4 = Write.
 */
final class GenerateCommand extends Command
{
    protected $signature = 'migrate:generate
        {tables? : Comma-separated tables to include (defaults to all)}
        {--connection= : Database connection name}
        {--ignore= : Comma-separated tables to exclude}
        {--tables= : Alias for the positional argument}
        {--path= : Output directory (default: database/migrations)}
        {--stub-path= : Custom stub directory}
        {--force : Overwrite existing files}';

    protected $description = 'Generate Laravel migrations from an existing database.';

    public function handle(
        SchemaDriverFactory $factory,
        ReadDatabaseAction $read,
        BuildMigrationPlanAction $plan,
        RenderMigrationAction $render,
        WriteMigrationFileAction $write,
    ): int {
        try {
            $options = $this->buildOptions();
            $this->info("Reading schema from connection: {$options->connection}");

            $driver = $factory->forConnection($options->connection);
            $schema = $read->execute($driver, $options);

            if ($schema->tables === []) {
                $this->warn('No tables to generate.');

                return self::SUCCESS;
            }

            $built = $plan->execute($schema, $options);
            $rendered = $render->execute($built);
            $paths = $write->execute($rendered, $options);

            $this->info(sprintf('Generated %d migration file(s):', count($paths)));
            foreach ($paths as $p) {
                $this->line("  - $p");
            }

            return self::SUCCESS;
        } catch (ConfigurationException $e) {
            $this->error('Configuration error: '.$e->getMessage());

            return 1;
        } catch (SchemaReadException $e) {
            $this->error('Schema read error: '.$e->getMessage());

            return 2;
        } catch (GenerationException $e) {
            $this->error('Generation error: '.$e->getMessage());

            return 3;
        } catch (WriteException $e) {
            $this->error('Write error: '.$e->getMessage());

            return 4;
        }
    }

    private function buildOptions(): GenerateOptions
    {
        $connectionOpt = $this->option('connection');
        $defaultConn = config('database.default');
        $connection = is_string($connectionOpt) && $connectionOpt !== ''
            ? $connectionOpt
            : (is_string($defaultConn) ? $defaultConn : '');

        $tablesArgRaw = $this->argument('tables') ?? $this->option('tables') ?? '';
        $tablesArg = is_string($tablesArgRaw) ? $tablesArgRaw : '';
        $tables = $tablesArg === '' ? [] : array_map('trim', explode(',', $tablesArg));

        $ignoreArg = is_string($this->option('ignore')) ? $this->option('ignore') : '';
        $ignored = $ignoreArg === '' ? [] : array_map('trim', explode(',', $ignoreArg));

        $pathOpt = $this->option('path');
        $path = is_string($pathOpt) && $pathOpt !== '' ? $pathOpt : database_path('migrations');

        $stubPathOpt = $this->option('stub-path');
        $stubPath = is_string($stubPathOpt) && $stubPathOpt !== '' ? $stubPathOpt : null;

        return new GenerateOptions(
            connection: $connection,
            tables: $tables,
            ignored: $ignored,
            path: $path,
            stubPath: $stubPath,
            force: (bool) $this->option('force'),
            date: date('Y_m_d_His'),
        );
    }
}
