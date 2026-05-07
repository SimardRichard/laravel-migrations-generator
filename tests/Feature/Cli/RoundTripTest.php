<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('round-trip: dump → generate → fresh migrate → dump matches structurally', function () {
    $outputDir = sys_get_temp_dir().'/rt-'.uniqid();
    mkdir($outputDir);

    DB::statement('CREATE TABLE animals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        species varchar(80) NOT NULL,
        age INTEGER,
        is_endangered TINYINT(1) NOT NULL DEFAULT 0
    )');

    DB::statement('CREATE TABLE observations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        animal_id INTEGER NOT NULL,
        notes TEXT,
        CONSTRAINT fk_obs_animal FOREIGN KEY (animal_id) REFERENCES animals(id)
    )');

    $originalAnimals = Schema::getColumnListing('animals');
    $originalObservations = Schema::getColumnListing('observations');

    Artisan::call('migrate:generate', ['--path' => $outputDir]);

    Schema::dropIfExists('observations');
    Schema::dropIfExists('animals');

    Artisan::call('migrate', ['--path' => $outputDir, '--realpath' => true]);

    expect(Schema::getColumnListing('animals'))->toBe($originalAnimals)
        ->and(Schema::getColumnListing('observations'))->toBe($originalObservations);

    File::deleteDirectory($outputDir);
});
