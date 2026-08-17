<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates TypeScript interface definition when --types is passed', function () {
    $tsPath = resource_path('js/types/TsArticle.d.ts');

    if (File::exists($tsPath)) File::delete($tsPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_ts_articles_table',
        '--types' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($tsPath))->toBeTrue();
    expect(File::get($tsPath))->toContain('export interface TsArticle');

    // Cleanup
    File::delete($tsPath);
});
