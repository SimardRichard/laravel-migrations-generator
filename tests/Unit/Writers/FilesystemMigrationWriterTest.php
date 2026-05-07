<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\FileAlreadyExistsException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\DTOs\MigrationFile;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Writers\FilesystemMigrationWriter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/mw-'.uniqid();
    mkdir($this->dir);
    $this->w = new FilesystemMigrationWriter(new Filesystem);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*') ?: []);
    rmdir($this->dir);
});

it('writes a file when path does not exist', function () {
    $f = new MigrationFile('a.php', '<?php // hello');
    $this->w->write($f, $this->dir, force: false);
    expect(file_get_contents($this->dir.'/a.php'))->toBe('<?php // hello');
});

it('throws when path exists and force is false', function () {
    file_put_contents($this->dir.'/a.php', 'existing');
    $this->w->write(new MigrationFile('a.php', 'new'), $this->dir, force: false);
})->throws(FileAlreadyExistsException::class);

it('overwrites when force is true', function () {
    file_put_contents($this->dir.'/a.php', 'existing');
    $this->w->write(new MigrationFile('a.php', 'new'), $this->dir, force: true);
    expect(file_get_contents($this->dir.'/a.php'))->toBe('new');
});
