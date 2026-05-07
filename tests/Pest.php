<?php

declare(strict_types=1);

use App\Modules\Migration\Tests\TestCase;

uses(TestCase::class)->in('Feature');

afterEach(function () {
    if (class_exists(Mockery::class)) {
        Mockery::close();
    }
});
