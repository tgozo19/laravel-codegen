<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates repository contract and eloquent repository when --repository is passed', function () {
    $interfacePath = app_path('Repositories/Contracts/RepoPostRepositoryInterface.php');
    $eloquentPath = app_path('Repositories/Eloquent/RepoPostRepository.php');

    if (File::exists($interfacePath)) File::delete($interfacePath);
    if (File::exists($eloquentPath)) File::delete($eloquentPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_repo_posts_table',
        '--repository' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($interfacePath))->toBeTrue();
    expect(File::exists($eloquentPath))->toBeTrue();

    // Cleanup
    File::delete($interfacePath);
    File::delete($eloquentPath);
});
