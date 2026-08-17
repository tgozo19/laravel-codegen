<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates store and update form requests when --requests is passed', function () {
    $storePath = app_path('Http/Requests/StoreFormReqArticleRequest.php');
    $updatePath = app_path('Http/Requests/UpdateFormReqArticleRequest.php');

    if (File::exists($storePath)) File::delete($storePath);
    if (File::exists($updatePath)) File::delete($updatePath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_form_req_articles_table',
        '--requests' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($storePath))->toBeTrue();
    expect(File::exists($updatePath))->toBeTrue();

    // Cleanup
    File::delete($storePath);
    File::delete($updatePath);
});
