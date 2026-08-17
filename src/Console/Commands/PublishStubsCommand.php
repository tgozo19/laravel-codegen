<?php

namespace Tgozo\LaravelCodegen\Console\Commands;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Console\BaseTrait;

class PublishStubsCommand extends Command
{
    use BaseTrait;

    protected $signature = 'codegen:publish-stubs {--force : Overwrite existing published stubs}';

    protected $description = 'Publish Laravel CodeGen stubs to resources/stubs/vendor/laravelcodegen for customization';

    public function handle(): int
    {
        $targetDir = base_path('resources/stubs/vendor/laravelcodegen');
        $sourceDir = dirname(__DIR__, 2) . '/stubs';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $files = scandir($sourceDir);
        if ($files === false) {
            $this->error('Failed to read package stubs directory');
            return Command::FAILURE;
        }

        $publishedCount = 0;
        $skippedCount = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $sourcePath = "{$sourceDir}/{$file}";
            $targetPath = "{$targetDir}/{$file}";

            if (file_exists($targetPath) && !$this->option('force')) {
                $skippedCount++;
                continue;
            }

            copy($sourcePath, $targetPath);
            $publishedCount++;
        }

        $this->info("Published {$publishedCount} stub(s) to [resources/stubs/vendor/laravelcodegen].");
        if ($skippedCount > 0) {
            $this->comment("Skipped {$skippedCount} existing stub(s). Use --force to overwrite.");
        }

        return Command::SUCCESS;
    }
}
