<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SquashMigrationsCommand extends Command
{
    protected $signature = 'make:codegen-squash {name : Table name to squash migrations for}';

    protected $description = 'Squash multiple migration files for a table into a single base migration';

    public function handle(): int
    {
        $tableName = Str::snake(trim($this->argument('name')));
        $migrationsDir = database_path('migrations');

        if (!File::isDirectory($migrationsDir)) {
            $this->error("Migrations directory does not exist.");
            return Command::FAILURE;
        }

        $files = File::files($migrationsDir);
        $matchingFiles = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (str_contains($filename, "_{$tableName}_table.php")) {
                $matchingFiles[] = $file;
            }
        }

        if (count($matchingFiles) <= 1) {
            $this->info("Found " . count($matchingFiles) . " migration file(s) for table [{$tableName}]. No squashing required.");
            return Command::SUCCESS;
        }

        $this->warn("Found " . count($matchingFiles) . " migration files for table [{$tableName}]:");
        foreach ($matchingFiles as $file) {
            $this->line("  - " . $file->getFilename());
        }

        $archiveDir = database_path("migrations/archived_{$tableName}_" . time());
        File::ensureDirectoryExists($archiveDir);

        foreach ($matchingFiles as $file) {
            File::move($file->getPathname(), $archiveDir . '/' . $file->getFilename());
        }

        $timestamp = date('Y_m_d_His');
        $newMigrationName = "{$timestamp}_create_{$tableName}_table.php";
        $newMigrationPath = "{$migrationsDir}/{$newMigrationName}";

        $className = "Create" . Str::studly($tableName) . "Table";

        $content = "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n        Schema::create('{$tableName}', function (Blueprint \$table) {\n            \$table->id();\n            \$table->timestamps();\n        });\n    }\n\n    public function down(): void\n    {\n        Schema::dropIfExists('{$tableName}');\n    }\n};\n";

        File::put($newMigrationPath, $content);

        $this->info("Successfully squashed " . count($matchingFiles) . " migrations into [{$newMigrationName}].");
        $this->comment("Archived previous migrations to [{$archiveDir}].");

        return Command::SUCCESS;
    }
}
