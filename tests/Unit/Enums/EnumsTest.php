<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Enums\ColumnType;
use App\Modules\Migration\Modules\Migration\Enums\IndexType;
use App\Modules\Migration\Modules\Migration\Enums\OnAction;

it('exposes representative ColumnType cases', function () {
    expect(ColumnType::String->value)->toBe('string')
        ->and(ColumnType::BigInteger->value)->toBe('bigInteger')
        ->and(ColumnType::Json->value)->toBe('json')
        ->and(ColumnType::Jsonb->value)->toBe('jsonb')
        ->and(ColumnType::Decimal->value)->toBe('decimal')
        ->and(ColumnType::Raw->value)->toBe('raw');
});

it('exposes IndexType families', function () {
    expect(IndexType::Primary->value)->toBe('primary')
        ->and(IndexType::Unique->value)->toBe('unique')
        ->and(IndexType::Index->value)->toBe('index')
        ->and(IndexType::FullText->value)->toBe('fullText')
        ->and(IndexType::Spatial->value)->toBe('spatialIndex');
});

it('parses OnAction from raw SQL', function () {
    expect(OnAction::fromSql('CASCADE'))->toBe(OnAction::Cascade)
        ->and(OnAction::fromSql('SET NULL'))->toBe(OnAction::SetNull)
        ->and(OnAction::fromSql('no action'))->toBe(OnAction::NoAction)
        ->and(OnAction::fromSql('UNKNOWN'))->toBe(OnAction::NoAction);
});
