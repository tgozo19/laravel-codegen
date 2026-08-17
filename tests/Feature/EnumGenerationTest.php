<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates backed string enum when --enum is passed', function () {
    $enumPath = app_path('Enums/EnumArticleStatusEnum.php');

    if (File::exists($enumPath)) File::delete($enumPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_enum_articles_table',
        '--enum' => 'EnumArticleStatus:draft,published,archived',
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($enumPath))->toBeTrue();
    expect(File::get($enumPath))->toContain('enum EnumArticleStatusEnum: string');

    // Cleanup
    File::delete($enumPath);
});
