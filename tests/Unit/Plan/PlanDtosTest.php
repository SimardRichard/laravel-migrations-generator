<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

it('captures CLI options used by Plan 1', function () {
    $opts = new GenerateOptions('sqlite', ['users'], [], '/tmp', null, false, '2026_05_07_120000');
    expect($opts->tables)->toBe(['users'])->and($opts->force)->toBeFalse();
});

it('represents a single migration file', function () {
    $f = new MigrationFile('2026_05_07_120000_create_tables.php', '<?php');
    expect($f->filename)->toEndWith('_create_tables.php');
});

it('aggregates files in a plan', function () {
    $plan = new MigrationPlan([new MigrationFile('a.php', ''), new MigrationFile('b.php', '')]);
    expect($plan->files)->toHaveCount(2)->and($plan->isEmpty())->toBeFalse();
});

it('reports empty plans', function () {
    expect((new MigrationPlan([]))->isEmpty())->toBeTrue();
});
