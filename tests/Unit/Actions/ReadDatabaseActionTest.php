<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\DTOs\ColumnSchema;
use App\Modules\Migration\Modules\Migration\DTOs\TableSchema;
use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Interfaces\SchemaDriver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Actions\ReadDatabaseAction;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\GenerateOptions;

it('reads all tables when no filter is provided', function () {
    $driver = Mockery::mock(SchemaDriver::class);
    $driver->shouldReceive('name')->andReturn('sqlite');
    $driver->shouldReceive('getTables')->andReturn(['users', 'posts']);
    $driver->shouldReceive('getColumns')->andReturn([
        new ColumnSchema('id', ColumnType::BigInteger, null, null, null, false, true, true, null, null, null, null),
    ]);
    $driver->shouldReceive('getIndexes')->andReturn([]);
    $driver->shouldReceive('getForeignKeys')->andReturn([]);

    $opts = new GenerateOptions('sqlite', [], [], '/tmp', null, false, '2026_05_07_120000');
    $schema = (new ReadDatabaseAction)->execute($driver, $opts);

    expect($schema->tables)->toHaveCount(2)->and($schema->connection)->toBe('sqlite');
});

it('filters tables and excludes ignored', function () {
    $driver = Mockery::mock(SchemaDriver::class);
    $driver->shouldReceive('name')->andReturn('sqlite');
    $driver->shouldReceive('getTables')->andReturn(['users', 'posts', 'logs']);
    $driver->shouldReceive('getColumns')->andReturn([]);
    $driver->shouldReceive('getIndexes')->andReturn([]);
    $driver->shouldReceive('getForeignKeys')->andReturn([]);

    $schema = (new ReadDatabaseAction)->execute(
        $driver,
        new GenerateOptions('sqlite', ['users', 'logs'], ['logs'], '/tmp', null, false, 'd'),
    );

    expect(array_map(fn (TableSchema $t): string => $t->name, $schema->tables))->toBe(['users']);
});
