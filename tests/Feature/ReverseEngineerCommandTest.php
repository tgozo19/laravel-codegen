<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('reverse engineers existing tables safely', function () {
    Schema::dropIfExists('test_posts');

    Schema::create('test_posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });

    $this->artisan('codegen:reverse-engineer', [
        '--tables' => ['test_posts'],
        '--models' => true,
        '--force' => true,
    ])->assertExitCode(0);

    Schema::dropIfExists('test_posts');
});
