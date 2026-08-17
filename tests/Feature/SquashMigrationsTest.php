<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('squashes multiple migration files for a table', function () {
    $dir = database_path('migrations');
    File::ensureDirectoryExists($dir);

    $file1 = $dir . '/2026_01_01_000000_create_squash_items_table.php';
    $file2 = $dir . '/2026_01_02_000000_add_status_to_squash_items_table.php';

    File::put($file1, "<?php // migration 1");
    File::put($file2, "<?php // migration 2");

    $this->artisan('make:codegen-squash', ['name' => 'squash_items'])
        ->assertExitCode(0);

    expect(File::exists($file1))->toBeFalse();
    expect(File::exists($file2))->toBeFalse();

    $archivedDirs = File::directories($dir);
    $archivedDir = collect($archivedDirs)->first(fn($d) => str_contains($d, 'archived_squash_items'));
    expect($archivedDir)->not->toBeNull();

    // Cleanup
    File::deleteDirectory($archivedDir);
    $squashedFiles = File::files($dir);
    foreach ($squashedFiles as $f) {
        if (str_contains($f->getFilename(), 'squash_items')) {
            File::delete($f->getPathname());
        }
    }
});
