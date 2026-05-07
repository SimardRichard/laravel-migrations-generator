<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->outputDir = sys_get_temp_dir().'/mg-out-'.uniqid();
    mkdir($this->outputDir);

    // SQL brut : Laravel Schema sur SQLite n'émet pas la longueur des
    // varchar — on en a besoin pour vérifier le rendu \$table->string('x', 200).
    DB::statement('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email varchar(200) NOT NULL,
        name varchar(100),
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
});

afterEach(fn () => File::deleteDirectory($this->outputDir));

it('generates two migration files for a schema with FKs', function () {
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir]))->toBe(0);

    $files = File::files($this->outputDir);
    expect($files)->toHaveCount(2);

    $names = array_map(fn ($f) => $f->getFilename(), $files);
    sort($names);

    expect($names[0])->toEndWith('_create_tables.php')
        ->and($names[1])->toEndWith('_add_foreign_keys.php');
});

it('produced migrations include create blocks and column directives', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);

    $createFile = collect(File::files($this->outputDir))
        ->first(fn ($f) => str_ends_with($f->getFilename(), '_create_tables.php'));

    expect($createFile)->not->toBeNull();

    $contents = (string) file_get_contents($createFile->getPathname());

    expect($contents)
        ->toContain("Schema::create('users'")
        ->toContain("Schema::create('posts'")
        ->toContain("\$table->string('email', 200)")
        ->toContain('->nullable()')
        ->toContain('return new class extends Migration');
});

it('refuses overwrite without --force', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir]))->toBe(4);
});

it('overwrites with --force', function () {
    Artisan::call('migrate:generate', ['--path' => $this->outputDir]);
    expect(Artisan::call('migrate:generate', ['--path' => $this->outputDir, '--force' => true]))->toBe(0);
});
