<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates Vue 3 Inertia components when --inertia is passed', function () {
    $indexPath = resource_path('js/Pages/InertiaArticle/Index.vue');
    $createPath = resource_path('js/Pages/InertiaArticle/Create.vue');

    if (File::exists($indexPath)) File::delete($indexPath);
    if (File::exists($createPath)) File::delete($createPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_inertia_articles_table',
        '--inertia' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($indexPath))->toBeTrue();
    expect(File::exists($createPath))->toBeTrue();

    // Cleanup
    File::delete($indexPath);
    File::delete($createPath);
});
