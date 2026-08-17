<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('removes generated artifacts for a model when codegen:clean is called', function () {
    $tsPath = resource_path('js/types/CleanTestModel.d.ts');
    
    File::ensureDirectoryExists(dirname($tsPath));
    File::put($tsPath, 'export interface CleanTestModel {}');

    expect(File::exists($tsPath))->toBeTrue();

    $this->artisan('codegen:clean', [
        'model' => 'CleanTestModel',
        '--force' => true,
    ])->assertExitCode(0);

    expect(File::exists($tsPath))->toBeFalse();
});
