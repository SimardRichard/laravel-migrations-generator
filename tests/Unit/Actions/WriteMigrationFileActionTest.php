<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Interfaces\MigrationWriter;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\WriteMigrationFileAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;

it('writes each file in the plan and returns paths in order', function () {
    $writer = Mockery::mock(MigrationWriter::class);
    $writer->shouldReceive('write')
        ->andReturnUsing(fn (MigrationFile $f): string => '/tmp/'.$f->filename);

    $plan = new MigrationPlan([new MigrationFile('a.php', ''), new MigrationFile('b.php', '')]);
    $opts = new GenerateOptions('sqlite', [], [], '/tmp', null, false, 'd');

    expect((new WriteMigrationFileAction($writer))->execute($plan, $opts))->toBe(['/tmp/a.php', '/tmp/b.php']);
});
