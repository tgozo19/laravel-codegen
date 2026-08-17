<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates React TSX Inertia components when --react is passed', function () {
    $indexPath = resource_path('js/Pages/ReactArticle/Index.tsx');
    $createPath = resource_path('js/Pages/ReactArticle/Create.tsx');

    if (File::exists($indexPath)) File::delete($indexPath);
    if (File::exists($createPath)) File::delete($createPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_react_articles_table',
        '--react' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($indexPath))->toBeTrue();
    expect(File::exists($createPath))->toBeTrue();

    // Cleanup
    File::delete($indexPath);
    File::delete($createPath);
});
