<?php

declare(strict_types=1);

namespace App\Modules\Migration\Tests;

use App\Modules\Migration\Providers\MigrationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MigrationServiceProvider::class];
    }
}
