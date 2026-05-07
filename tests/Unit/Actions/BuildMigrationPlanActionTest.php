<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;
use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\BuildMigrationPlanAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

beforeEach(function () {
    $this->action = new BuildMigrationPlanAction;
    $this->opts = fn (): GenerateOptions => new GenerateOptions('sqlite', [], [], '/tmp', null, false, '2026_05_07_120000');
});

it('creates two files when there are FKs', function () {
    $users = new TableSchema('users', null, null, null, [], [], []);
    $posts = new TableSchema('posts', null, null, null, [], [], [
        new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict),
    ]);

    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$posts, $users]), ($this->opts)());
    expect($plan->files)->toHaveCount(2)
        ->and($plan->files[0]->filename)->toEndWith('_create_tables.php')
        ->and($plan->files[1]->filename)->toEndWith('_add_foreign_keys.php');
});

it('creates a single file when no FKs', function () {
    $a = new TableSchema('a', null, null, null, [], [], []);
    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$a]), ($this->opts)());
    expect($plan->files)->toHaveCount(1);
});

it('orders tables topologically by FK dependencies', function () {
    $users = new TableSchema('users', null, null, null, [], [], []);
    $posts = new TableSchema('posts', null, null, null, [], [], [
        new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict),
    ]);

    $plan = $this->action->execute(new DatabaseSchema('sqlite', [$posts, $users]), ($this->opts)());
    $contents = $plan->files[0]->contents;
    expect(strpos($contents, "create('users'"))->toBeLessThan(strpos($contents, "create('posts'"));
});

it('throws on circular FK', function () {
    $a = new TableSchema('a', null, null, null, [], [], [
        new ForeignKeySchema('fk_a_b', ['b_id'], 'b', ['id'], OnAction::NoAction, OnAction::NoAction),
    ]);
    $b = new TableSchema('b', null, null, null, [], [], [
        new ForeignKeySchema('fk_b_a', ['a_id'], 'a', ['id'], OnAction::NoAction, OnAction::NoAction),
    ]);

    $this->action->execute(new DatabaseSchema('sqlite', [$a, $b]), ($this->opts)());
})->throws(CircularForeignKeyException::class);
