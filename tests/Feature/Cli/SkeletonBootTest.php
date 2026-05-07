<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('discovers the 3 stub commands', function () {
    Artisan::call('list');
    $output = Artisan::output();

    expect($output)
        ->toContain('migrate:generate')
        ->toContain('migrate:extract')
        ->toContain('migrate:import');
});

it('exposes migrate:generate signature', function () {
    Artisan::call('list');
    expect(Artisan::output())->toContain('migrate:generate');
});

it('runs migrate:extract stub successfully', function () {
    $exitCode = Artisan::call('migrate:extract');
    expect($exitCode)->toBe(0);
});

it('runs migrate:import stub successfully', function () {
    $exitCode = Artisan::call('migrate:import');
    expect($exitCode)->toBe(0);
});

it('respects the submodules config', function () {
    expect(config('migration-core.submodules.generator'))->toBeTrue()
        ->and(config('migration-core.submodules.extract'))->toBeTrue()
        ->and(config('migration-core.submodules.import'))->toBeTrue();
});
