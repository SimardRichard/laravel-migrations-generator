<?php

declare(strict_types=1);

use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Modules\Generator\Stub\DefaultStubRenderer;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/stubs-'.uniqid();
    mkdir($this->dir);
    file_put_contents($this->dir.'/sample.stub', 'Hello {{name}}, you are {{age}}!');
    $this->r = new DefaultStubRenderer($this->dir);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*.stub') ?: []);
    rmdir($this->dir);
});

it('replaces placeholders by their values', function () {
    expect($this->r->render('sample', ['name' => 'Alice', 'age' => '30']))->toBe('Hello Alice, you are 30!');
});

it('throws when stub does not exist', function () {
    $this->r->render('missing', []);
})->throws(StubNotFoundException::class);

it('leaves unmatched placeholders untouched', function () {
    expect($this->r->render('sample', ['name' => 'Bob']))->toBe('Hello Bob, you are {{age}}!');
});
