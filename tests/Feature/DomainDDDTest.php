<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates files in domain path when --domain is passed', function () {
    $domainReqPath = app_path('Domain/Blog/Requests/StoreDddPostRequest.php');

    if (File::exists($domainReqPath)) File::delete($domainReqPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_ddd_posts_table',
        '--requests' => true,
        '--domain' => 'Blog',
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($domainReqPath))->toBeTrue();

    // Cleanup
    File::deleteDirectory(app_path('Domain/Blog'));
});
