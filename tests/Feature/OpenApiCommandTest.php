<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates openapi.json spec when codegen:openapi is called', function () {
    $outputPath = base_path('openapi-test.json');

    if (File::exists($outputPath)) File::delete($outputPath);

    $this->artisan('codegen:openapi', ['--output' => 'openapi-test.json'])
        ->assertExitCode(0);

    expect(File::exists($outputPath))->toBeTrue();
    expect(File::get($outputPath))->toContain('"openapi": "3.0.0"');

    // Cleanup
    File::delete($outputPath);
});
