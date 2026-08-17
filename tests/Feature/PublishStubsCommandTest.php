<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('publishes package stubs to resources/stubs/vendor/laravelcodegen', function () {
    $targetPath = base_path('resources/stubs/vendor/laravelcodegen');
    
    if (File::exists($targetPath)) {
        File::deleteDirectory($targetPath);
    }

    $this->artisan('codegen:publish-stubs')
        ->assertExitCode(0);

    expect(File::exists($targetPath))->toBeTrue();
    expect(File::exists($targetPath . '/reverse-engineer.model.stub'))->toBeTrue();

    // Cleanup after test
    File::deleteDirectory($targetPath);
});
