<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\RawDefault;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ColumnRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\ForeignKeyRenderer;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Renderers\IndexRenderer;

beforeEach(function () {
    $this->col = new ColumnRenderer;
    $this->idx = new IndexRenderer;
    $this->fk = new ForeignKeyRenderer;
});

it('renders a basic string column', function () {
    $c = new ColumnSchema('email', ColumnType::String, 255, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('email', 255);");
});

it('omits length when null', function () {
    $c = new ColumnSchema('label', ColumnType::String, null, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('label');");
});

it('renders nullable + default modifiers', function () {
    $c = new ColumnSchema('status', ColumnType::String, 20, null, null, true, false, false, 'draft', null, null, null);
    expect($this->col->render($c))->toBe("\$table->string('status', 20)->nullable()->default('draft');");
});

it('renders bigIncrements for auto-increment unsigned BigInteger primary keys', function () {
    $c = new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->bigIncrements('id');");
});

it('renders DB::raw for raw default expressions', function () {
    $c = new ColumnSchema('created_at', ColumnType::Timestamp, null, null, null, false, false, false, new RawDefault('CURRENT_TIMESTAMP'), null, null, null);
    expect($this->col->render($c))->toBe("\$table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));");
});

it('renders decimal precision and scale', function () {
    $c = new ColumnSchema('price', ColumnType::Decimal, null, 12, 2, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toBe("\$table->decimal('price', 12, 2);");
});

it('falls back to raw column for ColumnType::Raw', function () {
    $c = new ColumnSchema('weird', ColumnType::Raw, null, null, null, false, false, false, null, null, null, null);
    expect($this->col->render($c))->toContain("\$table->addColumn('text', 'weird')")
        ->and($this->col->render($c))->toContain('// unmapped');
});

it('skips PRIMARY index (handled by bigIncrements)', function () {
    $i = new IndexSchema('PRIMARY', IndexType::Primary, ['id'], false, null, null);
    expect($this->idx->render($i))->toBeNull();
});

it('renders unique index', function () {
    $i = new IndexSchema('users_email_unique', IndexType::Unique, ['email'], false, null, null);
    expect($this->idx->render($i))->toBe("\$table->unique('email', 'users_email_unique');");
});

it('renders composite unique index', function () {
    $i = new IndexSchema('idx_org_email', IndexType::Unique, ['org_id', 'email'], true, null, null);
    expect($this->idx->render($i))->toBe("\$table->unique(['org_id', 'email'], 'idx_org_email');");
});

it('renders simple FK with cascade/restrict', function () {
    $f = new ForeignKeySchema('fk_posts_user', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict);
    expect($this->fk->render($f))->toBe(
        "\$table->foreign('user_id', 'fk_posts_user')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');",
    );
});

it('renders composite FK', function () {
    $f = new ForeignKeySchema('fk_x', ['a', 'b'], 'parent', ['x', 'y'], OnAction::NoAction, OnAction::NoAction);
    expect($this->fk->render($f))->toBe(
        "\$table->foreign(['a', 'b'], 'fk_x')->references(['x', 'y'])->on('parent')->onUpdate('no action')->onDelete('no action');",
    );
});
