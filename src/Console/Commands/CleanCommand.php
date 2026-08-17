<?php

namespace Tgozo\LaravelCodegen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CleanCommand extends Command
{
    protected $signature = 'codegen:clean 
                            {model : Model or module name to clean up}
                            {--domain= : Domain/DDD namespace if applicable}
                            {--force : Delete files without asking confirmation}';

    protected $description = 'Safely remove generated module files (Models, Controllers, Requests, Views, Tests, Types)';

    public function handle(): int
    {
        $modelInput = $this->argument('model');
        $modelName = Str::studly($modelInput);
        $modelPlural = Str::plural($modelName);
        $tableName = Str::snake($modelPlural);
        $domain = $this->option('domain');

        $files = [];

        // Model
        $modelPath = !empty($domain)
            ? app_path("Domain/" . Str::studly($domain) . "/Models/{$modelName}.php")
            : app_path("Models/{$modelName}.php");
        if (File::exists($modelPath)) $files[] = $modelPath;

        // Controller
        $controllerPath = !empty($domain)
            ? app_path("Domain/" . Str::studly($domain) . "/Controllers/{$modelName}Controller.php")
            : app_path("Http/Controllers/{$modelName}Controller.php");
        if (File::exists($controllerPath)) $files[] = $controllerPath;

        // Requests
        $reqDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Requests" : "Http/Requests";
        $storeReq = app_path("{$reqDir}/Store{$modelName}Request.php");
        $updateReq = app_path("{$reqDir}/Update{$modelName}Request.php");
        if (File::exists($storeReq)) $files[] = $storeReq;
        if (File::exists($updateReq)) $files[] = $updateReq;

        // Blade Views Directory
        $bladeDir = resource_path("views/{$tableName}");
        if (File::isDirectory($bladeDir)) $files[] = $bladeDir;

        // Inertia Components Directory
        $inertiaDir = resource_path("js/Pages/{$modelName}");
        if (File::isDirectory($inertiaDir)) $files[] = $inertiaDir;

        // TypeScript Definition
        $tsPath = resource_path("js/types/{$modelName}.d.ts");
        if (File::exists($tsPath)) $files[] = $tsPath;

        // Factory & Seeder
        $factoryPath = database_path("factories/{$modelName}Factory.php");
        $seederPath = database_path("seeders/{$modelName}Seeder.php");
        if (File::exists($factoryPath)) $files[] = $factoryPath;
        if (File::exists($seederPath)) $files[] = $seederPath;

        // Tests
        $testPath = base_path("tests/Feature/{$modelName}Test.php");
        if (File::exists($testPath)) $files[] = $testPath;

        if (empty($files)) {
            $this->comment("No generated artifacts found for [{$modelName}].");
            return Command::SUCCESS;
        }

        $this->warn("Found " . count($files) . " artifact(s) for module [{$modelName}]:");
        foreach ($files as $file) {
            $this->line("  - {$file}");
        }

        if (!$this->option('force') && !$this->confirm('Are you sure you want to delete these files/directories?', false)) {
            $this->info("Cleanup cancelled.");
            return Command::SUCCESS;
        }

        $deletedCount = 0;
        foreach ($files as $file) {
            if (File::isDirectory($file)) {
                File::deleteDirectory($file);
            } else {
                File::delete($file);
            }
            $deletedCount++;
        }

        $this->info("Successfully cleaned up {$deletedCount} artifact(s) for [{$modelName}].");

        return Command::SUCCESS;
    }
}
