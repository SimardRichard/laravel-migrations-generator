<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Console\Commands;

use Illuminate\Console\Command;

final class GenerateCommand extends Command
{
    protected $signature = 'migrate:generate';

    protected $description = 'Generate Laravel migrations from an existing database (stub for Phase 0).';

    public function handle(): int
    {
        $this->warn('migrate:generate is not implemented yet (Phase 0 skeleton).');

        return self::SUCCESS;
    }
}
