<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\DatabaseSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

it('constructs ColumnSchema with required + optional metadata', function () {
    $col = new ColumnSchema('email', ColumnType::String, 255, null, null, false, false, false, null, null, null, null);
    expect($col->name)->toBe('email')->and($col->length)->toBe(255);
});

it('constructs IndexSchema with composite columns', function () {
    $idx = new IndexSchema('idx', IndexType::Index, ['a', 'b'], true, null, null);
    expect($idx->columns)->toHaveCount(2)->and($idx->isComposite)->toBeTrue();
});

it('constructs ForeignKeySchema with both actions', function () {
    $fk = new ForeignKeySchema('fk', ['user_id'], 'users', ['id'], OnAction::Cascade, OnAction::Restrict);
    expect($fk->referencedTable)->toBe('users')->and($fk->onDelete)->toBe(OnAction::Restrict);
});

it('TableSchema aggregates columns/indexes/fks', function () {
    $col = new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null);
    $t = new TableSchema('users', null, null, null, [$col], [], []);
    expect($t->columns)->toHaveCount(1);
});

it('DatabaseSchema can find a table by name', function () {
    $t = new TableSchema('users', null, null, null, [], [], []);
    $db = new DatabaseSchema('sqlite', [$t]);
    expect($db->table('users'))->toBe($t)
        ->and($db->table('missing'))->toBeNull();
});
