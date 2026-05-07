<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\RenderMigrationAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationPlan;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;

beforeEach(function () {
    $this->stubDir = sys_get_temp_dir().'/stubs-'.uniqid();
    mkdir($this->stubDir);

    file_put_contents($this->stubDir.'/migration.create.stub', "<?php\n[CREATE]\n{{up_body}}\n{{down_body}}");
    file_put_contents($this->stubDir.'/migration.foreign-keys.stub', "<?php\n[FK]\n{{up_body}}\n{{down_body}}");

    $this->action = new RenderMigrationAction(new DefaultStubRenderer($this->stubDir));
});

afterEach(function () {
    array_map('unlink', glob($this->stubDir.'/*') ?: []);
    rmdir($this->stubDir);
});

$separator = BuildMigrationPlanAction::BODY_SEPARATOR;

it('wraps create_tables files in the create stub', function () use ($separator) {
    $f = new MigrationFile('2026_05_07_120000_00_create_tables.php', 'UP_X'.$separator.'DOWN_X');
    $rendered = $this->action->execute(new MigrationPlan([$f]));
    expect($rendered->files[0]->contents)->toContain('[CREATE]')
        ->toContain('UP_X')
        ->toContain('DOWN_X');
});

it('wraps add_foreign_keys files in the FK stub', function () use ($separator) {
    $f = new MigrationFile('2026_05_07_120000_01_add_foreign_keys.php', 'UP_FK'.$separator.'DOWN_FK');
    $rendered = $this->action->execute(new MigrationPlan([$f]));
    expect($rendered->files[0]->contents)->toContain('[FK]');
});
