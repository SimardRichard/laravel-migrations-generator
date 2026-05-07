<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\CircularForeignKeyException;
use App\Modules\Migration\Modules\Migration\Exceptions\ConfigurationException;
use App\Modules\Migration\Modules\Migration\Exceptions\DatabaseAccessDeniedException;
use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Exceptions\GenerationException;
use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\MigrationCoreException;
use App\Modules\Migration\Modules\Migration\Exceptions\SchemaReadException;
use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Exceptions\TableNotFoundException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedColumnTypeException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteException;
use App\Modules\Migration\Modules\Migration\Exceptions\WriteFailedException;

it('roots all package exceptions in MigrationCoreException', function () {
    expect(new ConfigurationException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new SchemaReadException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new GenerationException('x'))->toBeInstanceOf(MigrationCoreException::class)
        ->and(new WriteException('x'))->toBeInstanceOf(MigrationCoreException::class);
});

it('UnsupportedDriverException carries context', function () {
    $e = UnsupportedDriverException::for('cockroachdb');
    expect($e->context())->toBe(['driver' => 'cockroachdb'])
        ->and($e)->toBeInstanceOf(ConfigurationException::class);
});

it('InvalidConnectionException carries context', function () {
    $e = InvalidConnectionException::for('legacy');
    expect($e->context())->toBe(['connection' => 'legacy']);
});

it('TableNotFoundException carries context', function () {
    expect(TableNotFoundException::for('orders')->context())->toBe(['table' => 'orders']);
});

it('DatabaseAccessDeniedException carries context', function () {
    expect(DatabaseAccessDeniedException::for('readonly')->context())->toBe(['connection' => 'readonly']);
});

it('UnsupportedColumnTypeException carries full context', function () {
    $e = UnsupportedColumnTypeException::for('users', 'location', 'geometry', 'mysql');
    expect($e->context())->toBe([
        'table' => 'users', 'column' => 'location', 'type' => 'geometry', 'driver' => 'mysql',
    ]);
});

it('StubNotFoundException carries path', function () {
    expect(StubNotFoundException::for('/tmp/x.stub')->context())->toBe(['path' => '/tmp/x.stub']);
});

it('CircularForeignKeyException carries cycle', function () {
    expect(CircularForeignKeyException::for(['a', 'b', 'a'])->context())->toBe(['cycle' => ['a', 'b', 'a']]);
});

it('FileAlreadyExistsException carries path', function () {
    expect(FileAlreadyExistsException::for('/tmp/x.php')->context())->toBe(['path' => '/tmp/x.php']);
});

it('WriteFailedException carries path and reason', function () {
    expect(WriteFailedException::for('/tmp/x', 'denied')->context())
        ->toBe(['path' => '/tmp/x', 'reason' => 'denied']);
});
