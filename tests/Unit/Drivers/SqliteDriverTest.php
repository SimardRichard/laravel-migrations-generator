<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\ForeignKeySchema;
use App\Modules\Migration\Modules\Migration\DTOs\IndexSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SqliteDriver;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;
use App\Modules\Migration\Tests\TestCase;
use Illuminate\Support\Facades\DB;

uses(TestCase::class);

beforeEach(function () {
    // SQL brut : le grammar SQLite de Laravel n'émet pas la longueur des
    // varchar, or on veut tester l'extraction de longueur côté driver.
    DB::statement('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email varchar(200) NOT NULL,
        nickname varchar(60),
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

    DB::statement('CREATE TABLE posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title varchar(255) NOT NULL,
        body TEXT NOT NULL,
        CONSTRAINT fk_posts_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');
    DB::statement('CREATE INDEX posts_title_idx ON posts (title)');

    $this->driver = new SqliteDriver(
        connection: DB::connection(),
        columnTypeResolver: new ColumnTypeResolver,
        defaultValueResolver: new DefaultValueResolver,
    );
});

it('lists tables in alphabetical order', function () {
    expect($this->driver->getTables())->toBe(['posts', 'users']);
});

it('reads users columns', function () {
    /** @var ColumnSchema[] $cols */
    $cols = $this->driver->getColumns('users');
    $names = array_map(fn (ColumnSchema $c): string => $c->name, $cols);
    expect($names)->toContain('id', 'email', 'nickname', 'is_active', 'created_at')
        ->and($cols[1]->type)->toBe(ColumnType::String)
        ->and($cols[1]->length)->toBe(200);
});

it('detects unique index on email', function () {
    /** @var IndexSchema[] $indexes */
    $indexes = $this->driver->getIndexes('users');
    $names = array_map(fn (IndexSchema $i): string => $i->name, $indexes);
    expect($names)->toContain('users_email_unique');
});

it('detects FK on posts.user_id', function () {
    /** @var ForeignKeySchema[] $fks */
    $fks = $this->driver->getForeignKeys('posts');
    expect($fks)->toHaveCount(1)
        ->and($fks[0]->referencedTable)->toBe('users')
        ->and($fks[0]->columns)->toBe(['user_id']);
});

it('reports its driver name', fn () => expect($this->driver->name())->toBe('sqlite'));
