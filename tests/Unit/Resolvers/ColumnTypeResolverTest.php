<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Services\ColumnTypeResolver;

beforeEach(fn () => $this->r = new ColumnTypeResolver);

it('maps SQLite native types to ColumnType enum', function (string $sqlite, ColumnType $expected) {
    expect($this->r->forSqlite($sqlite))->toBe($expected);
})->with([
    ['INTEGER', ColumnType::Integer],
    ['integer', ColumnType::Integer],
    ['BIGINT', ColumnType::BigInteger],
    ['VARCHAR(255)', ColumnType::String],
    ['TEXT', ColumnType::Text],
    ['BLOB', ColumnType::Binary],
    ['REAL', ColumnType::Double],
    ['NUMERIC(10,2)', ColumnType::Decimal],
    ['BOOLEAN', ColumnType::Boolean],
    ['DATE', ColumnType::Date],
    ['DATETIME', ColumnType::DateTime],
    ['TIMESTAMP', ColumnType::Timestamp],
    ['JSON', ColumnType::Json],
    ['UUID', ColumnType::Uuid],
]);

it('falls back to Raw for unrecognized types', function () {
    expect($this->r->forSqlite('CUSTOM_FANCY'))->toBe(ColumnType::Raw);
});
