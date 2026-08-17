<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('registers wizard command successfully', function () {
    $this->artisan('codegen:wizard', ['--help' => true])
        ->assertExitCode(0);
});
