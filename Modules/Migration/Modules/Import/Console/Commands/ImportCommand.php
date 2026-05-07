<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Import\Console\Commands;

use Illuminate\Console\Command;

final class ImportCommand extends Command
{
    protected $signature = 'migrate:import';

    protected $description = 'Import data from CSV/JSON/Excel files into a database (stub).';

    public function handle(): int
    {
        $this->warn('migrate:import is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
