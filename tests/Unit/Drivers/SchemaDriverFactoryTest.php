<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\InvalidConnectionException;
use App\Modules\Migration\Modules\Migration\Exceptions\UnsupportedDriverException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SchemaDriverFactory;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\SqliteDriver;
use App\Modules\Migration\Tests\TestCase;
use Illuminate\Support\Facades\Config;

uses(TestCase::class);

it('returns SqliteDriver for sqlite connection', function () {
    $factory = new SchemaDriverFactory(app());
    expect($factory->forConnection('sqlite'))->toBeInstanceOf(SqliteDriver::class);
});

it('throws on unknown driver', function () {
    Config::set('database.connections.fake', ['driver' => 'oracle', 'database' => ':memory:']);
    (new SchemaDriverFactory(app()))->forConnection('fake');
})->throws(UnsupportedDriverException::class);

it('throws on undefined connection', function () {
    (new SchemaDriverFactory(app()))->forConnection('does-not-exist');
})->throws(InvalidConnectionException::class);
