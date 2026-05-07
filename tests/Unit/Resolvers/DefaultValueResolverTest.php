<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\DefaultValueResolver;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers\RawDefault;

beforeEach(fn () => $this->r = new DefaultValueResolver);

it('returns null for SQL NULL', fn () => expect($this->r->resolve(null))->toBeNull());

it('preserves quoted string defaults', function () {
    expect($this->r->resolve("'draft'"))->toBe('draft')
        ->and($this->r->resolve('"hello"'))->toBe('hello');
});

it('returns numeric values typed', function () {
    expect($this->r->resolve('42'))->toBe(42)
        ->and($this->r->resolve('3.14'))->toBe(3.14);
});

it('detects CURRENT_TIMESTAMP family as raw expressions', function () {
    /** @var RawDefault $a */
    $a = $this->r->resolve('CURRENT_TIMESTAMP');
    /** @var RawDefault $b */
    $b = $this->r->resolve('current_timestamp');
    /** @var RawDefault $c */
    $c = $this->r->resolve('now()');

    expect($a->expression())->toBe('CURRENT_TIMESTAMP')
        ->and($b->expression())->toBe('CURRENT_TIMESTAMP')
        ->and($c->expression())->toBe('CURRENT_TIMESTAMP');
});

it('treats other parenthesized expressions as raw', function () {
    /** @var RawDefault $r */
    $r = $this->r->resolve("nextval('users_id_seq')");
    expect($r->expression())->toBe("nextval('users_id_seq')");
});

it('returns booleans for typical SQLite/PG forms', function () {
    expect($this->r->resolve('1'))->toBe(1)
        ->and($this->r->resolve('true'))->toBeTrue()
        ->and($this->r->resolve('FALSE'))->toBeFalse();
});
