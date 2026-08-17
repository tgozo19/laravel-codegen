<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('includes restore and forceDelete methods when --soft-deletes-actions is passed', function () {
    $controllerPath = app_path('Http/Controllers/SoftDelItemController.php');

    if (File::exists($controllerPath)) File::delete($controllerPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_soft_del_items_table',
        '-c' => true,
        '--soft-deletes-actions' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($controllerPath))->toBeTrue();
    expect(File::get($controllerPath))->toContain('public function restore');
    expect(File::get($controllerPath))->toContain('public function forceDelete');

    // Cleanup
    File::delete($controllerPath);
});
