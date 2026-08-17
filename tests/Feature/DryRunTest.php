<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('simulates generation without creating files when --dry-run is passed', function () {
    $storePath = app_path('Http/Requests/StoreProjectRequest.php');

    if (File::exists($storePath)) File::delete($storePath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_projects_table',
        '--requests' => true,
        '--dry-run' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($storePath))->toBeFalse();
});
