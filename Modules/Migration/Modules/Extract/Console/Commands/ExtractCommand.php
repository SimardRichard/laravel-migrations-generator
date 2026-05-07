<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Extract\Console\Commands;

use Illuminate\Console\Command;

final class ExtractCommand extends Command
{
    protected $signature = 'migrate:extract';

    protected $description = 'Extract data from a database to CSV/JSON/Excel files (stub).';

    public function handle(): int
    {
        $this->warn('migrate:extract is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
