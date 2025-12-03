<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CombineMigrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codegen:combine-migrations
                            {--table= : The table name to combine migrations for}
                            {--all : Combine all possible migrations}
                            {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combine modifier migrations (add/drop/rename columns) into their original create table migrations. Processes all tables by default.';

    protected string $migrationsPath;
    protected array $migrations = [];
    protected bool $isDryRun = false;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->migrationsPath = database_path('migrations');
        $this->isDryRun = $this->option('dry-run');

        if ($this->isDryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        $this->info('🔍 Scanning migrations...');

        if ($table = $this->option('table')) {
            $this->combineMigrationsForTable($table);
        } else {
            // Default to combining all migrations if no specific table is specified
            $this->combineAllMigrations();
        }

        $this->info('✅ Migration combination complete!');
    }

    /**
     * Combine all possible migrations
     */
    protected function combineAllMigrations(): void
    {
        $tables = $this->findAllCreateTables();

        if (empty($tables)) {
            $this->warn('No create table migrations found.');
            return;
        }

        $this->info(sprintf('Found %d create table migrations', count($tables)));

        foreach ($tables as $table) {
            $this->combineMigrationsForTable($table);
        }
    }

    /**
     * Find all create table migrations
     */
    protected function findAllCreateTables(): array
    {
        $files = File::glob($this->migrationsPath . '/*.php');
        $tables = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
                $tables[] = $matches[1];
            }
        }

        return array_unique($tables);
    }

    /**
     * Combine migrations for a specific table
     */
    protected function combineMigrationsForTable(string $table): void
    {
        $this->line("\n📋 Processing table: {$table}");

        // Find the create table migration
        $createMigration = $this->findCreateMigration($table);

        if (!$createMigration) {
            $this->warn("  ⚠️  Could not find create_{$table}_table migration");
            return;
        }

        // Find all modifier migrations for this table
        $modifierMigrations = $this->findModifierMigrations($table);

        if (empty($modifierMigrations)) {
            $this->line("  ℹ️  No modifier migrations found for {$table}");
        } else {
            $this->info(sprintf("  Found %d modifier migration(s)", count($modifierMigrations)));
        }

        // Sort modifier migrations by timestamp to maintain order
        usort($modifierMigrations, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        // Parse the create migration
        $createContent = File::get($createMigration);
        $modifiedContent = $createContent;

        $changesMade = 0;

        // Process each modifier migration
        foreach ($modifierMigrations as $modifier) {
            $this->line("  📝 Processing: {$modifier['filename']}");

            $modifierContent = File::get($modifier['path']);
            $operations = $this->parseModifierMigration($modifierContent, $modifier['type']);

            if (empty($operations)) {
                $this->warn("    ⚠️  Could not parse migration operations");
                continue;
            }

            // Apply operations to create migration
            $result = $this->applyOperationsToCreateMigration($modifiedContent, $operations, $modifier['type']);

            if ($result) {
                $modifiedContent = $result;
                $changesMade++;

                if (!$this->isDryRun) {
                    // Delete the modifier migration
                    File::delete($modifier['path']);
                    $this->info("    ✅ Merged and deleted {$modifier['filename']}");
                } else {
                    $this->info("    ✅ Would merge and delete {$modifier['filename']}");
                }
            }
        }

        // Apply formatting and cleanup (this handles deduplication too)
        $finalContent = $this->cleanupFormatting($modifiedContent);

        // Save if there are any changes (content merge OR formatting/cleanup)
        if ($finalContent !== $createContent) {
            if (!$this->isDryRun) {
                File::put($createMigration, $finalContent);
                $msg = $changesMade > 0
                    ? "with {$changesMade} change(s)"
                    : "(cleanup only)";
                $this->info("  ✅ Updated create_{$table}_table migration {$msg}");
            } else {
                $msg = $changesMade > 0
                    ? "with {$changesMade} change(s)"
                    : "(cleanup only)";
                $this->info("  ✅ Would update create_{$table}_table migration {$msg}");
            }
        }
    }

    /**
     * Clean up formatting in the modified content
     */
    protected function cleanupFormatting(string $content): string
    {
        // 1. Remove duplicates first
        $content = $this->removeDuplicateColumns($content);

        // 2. Basic cleanup
        // Remove multiple consecutive blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        // Ensure consistent line endings
        $content = str_replace("\r\n", "\n", $content);
        // Fix any trailing whitespace on lines
        $content = preg_replace('/[ \t]+$/m', '', $content);

        // 3. Fix multiple statements on one line (e.g. $table->id(); $table->string...)
        $content = preg_replace('/;\s*\$table->/', ";\n\$table->", $content);

        // 4. Robust formatting
        return $this->formatCodeByIndentation($content);
    }

    /**
     * Remove duplicate columns from Schema::create blocks
     */
    protected function removeDuplicateColumns(string $content): string
    {
        // Find Schema::create block and deduplicate lines inside it
        $pattern = '/(Schema::create\([\'"]\w+[\'"],\s*function\s*\(Blueprint\s*\$table\)\s*\{)([\s\S]*?)(\}\);)/';

        return preg_replace_callback($pattern, function ($matches) {
            $start = $matches[1];
            $body = $matches[2];
            $end = $matches[3];

            $deduplicatedBody = $this->deduplicateLines($body);

            return $start . $deduplicatedBody . $end;
        }, $content);
    }

    /**
     * Deduplicate lines in the migration body
     */
    protected function deduplicateLines(string $body): string
    {
        $lines = explode("\n", $body);
        $seen = [];
        $newLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip comments and empty lines
            if (empty($trimmed) || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '*')) {
                $newLines[] = $line;
                continue;
            }

            $colName = null;

            // Match named columns: $table->string('name')
            if (preg_match('/\$table->\w+\([\'"]([^\'"]+)[\'"]/', $trimmed, $m)) {
                $colName = $m[1];
            }
            // Match special columns: $table->timestamps()
            elseif (preg_match('/\$table->(timestamps|softDeletes|id|rememberToken)\(\)/', $trimmed, $m)) {
                $colName = $m[1];
            }

            if ($colName) {
                if (in_array($colName, $seen)) {
                    continue; // Skip duplicate
                }
                $seen[] = $colName;
            }

            $newLines[] = $line;
        }

        return implode("\n", $newLines);
    }

    /**
     * Format PHP code based on brace indentation
     */
    protected function formatCodeByIndentation(string $content): string
    {
        $lines = explode("\n", $content);
        $formattedLines = [];
        $indentLevel = 0;
        $indentString = '    '; // 4 spaces

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (empty($trimmedLine)) {
                $formattedLines[] = '';
                continue;
            }

            // Decrease indent if line starts with closing brace
            if (str_starts_with($trimmedLine, '}') || str_starts_with($trimmedLine, '];') || str_starts_with($trimmedLine, '],')) {
                $indentLevel = max(0, $indentLevel - 1);
            }

            // Apply current indentation
            $formattedLines[] = str_repeat($indentString, $indentLevel) . $trimmedLine;

            // Increase indent if line ends with opening brace or bracket
            // But not if it also contains a closing brace (single line block)
            $hasOpeningBrace = str_ends_with($trimmedLine, '{') || str_ends_with($trimmedLine, '[');
            $hasClosingBrace = str_contains($trimmedLine, '}') || str_contains($trimmedLine, ']');

            if ($hasOpeningBrace && !($hasClosingBrace && str_ends_with($trimmedLine, ';'))) {
                $indentLevel++;
            }

            // Special handling for Schema::create/table closures if they don't end with {
            if (preg_match('/Schema::.*function.*\(.*Blueprint.*\$table.*\)\s*$/', $trimmedLine)) {
                $indentLevel++;
            }
        }

        return implode("\n", $formattedLines);
    }

    /**
     * Find the create table migration file
     */
    protected function findCreateMigration(string $table): ?string
    {
        $files = File::glob($this->migrationsPath . "/*_create_{$table}_table.php");
        return $files[0] ?? null;
    }

    /**
     * Find all modifier migrations for a table
     */
    protected function findModifierMigrations(string $table): array
    {
        $files = File::glob($this->migrationsPath . '/*.php');
        $modifiers = [];

        $patterns = [
            'add_columns' => "/^(\d{4}_\d{2}_\d{2}_\d{6})_add_(.+)_to_{$table}_table\.php$/",
            'drop_columns' => "/^(\d{4}_\d{2}_\d{2}_\d{6})_drop_(.+)_from_{$table}_table\.php$/",
            'rename_columns' => "/^(\d{4}_\d{2}_\d{2}_\d{6})_rename_(.+)_in_{$table}_table\.php$/",
            'modify_table' => "/^(\d{4}_\d{2}_\d{2}_\d{6})_modify_{$table}_table\.php$/",
        ];

        foreach ($files as $file) {
            $filename = basename($file);

            foreach ($patterns as $type => $pattern) {
                if (preg_match($pattern, $filename, $matches)) {
                    $modifiers[] = [
                        'path' => $file,
                        'filename' => $filename,
                        'timestamp' => $matches[1],
                        'type' => $type,
                    ];
                    break;
                }
            }
        }

        return $modifiers;
    }

    /**
     * Parse a modifier migration to extract operations
     */
    protected function parseModifierMigration(string $content, string $type): array
    {
        $operations = [];

        // Extract the up() method content
        if (!preg_match('/public function up\(\)[\s\S]*?\{([\s\S]*?)\n    \}/', $content, $matches)) {
            return $operations;
        }

        $upContent = $matches[1];

        // Parse based on migration type
        switch ($type) {
            case 'add_columns':
                $operations = $this->parseAddColumns($upContent);
                break;
            case 'drop_columns':
                $operations = $this->parseDropColumns($upContent);
                break;
            case 'rename_columns':
                $operations = $this->parseRenameColumns($upContent);
                break;
            case 'modify_table':
                $operations = $this->parseModifyTable($upContent);
                break;
        }

        return $operations;
    }

    /**
     * Parse add column operations
     */
    protected function parseAddColumns(string $content): array
    {
        $operations = [];

        // Match column definitions like: $table->string('name');
        preg_match_all('/\$table->([a-zA-Z]+)\((.*?)\);/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fullLine = trim($match[0]);

            // Skip timestamps, softDeletes, etc.
            if (in_array($match[1], ['timestamps', 'softDeletes', 'id'])) {
                continue;
            }

            $operations[] = [
                'type' => 'add',
                'definition' => $fullLine,
            ];
        }

        return $operations;
    }

    /**
     * Parse drop column operations
     */
    protected function parseDropColumns(string $content): array
    {
        $operations = [];

        // Match dropColumn calls
        if (preg_match_all('/\$table->dropColumn\((.*?)\);/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                // Handle both array and string syntax
                $columns = $this->extractColumnNames($match);

                foreach ($columns as $column) {
                    $operations[] = [
                        'type' => 'drop',
                        'column' => $column,
                    ];
                }
            }
        }

        return $operations;
    }

    /**
     * Parse rename column operations
     */
    protected function parseRenameColumns(string $content): array
    {
        $operations = [];

        // Match renameColumn calls
        if (preg_match_all('/\$table->renameColumn\([\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\);/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $operations[] = [
                    'type' => 'rename',
                    'from' => $match[1],
                    'to' => $match[2],
                ];
            }
        }

        return $operations;
    }

    /**
     * Parse modify table operations (can contain add, drop, rename, etc.)
     */
    protected function parseModifyTable(string $content): array
    {
        $operations = [];

        // Try to parse as add operations first
        $addOps = $this->parseAddColumns($content);
        $dropOps = $this->parseDropColumns($content);
        $renameOps = $this->parseRenameColumns($content);

        return array_merge($addOps, $dropOps, $renameOps);
    }

    /**
     * Extract column names from a string (handles both array and string syntax)
     */
    protected function extractColumnNames(string $match): array
    {
        $columns = [];

        // Remove whitespace
        $match = trim($match);

        // Check if it's an array
        if (str_contains($match, '[')) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $match, $matches);
            $columns = $matches[1];
        } else {
            // Single column as string
            if (preg_match('/[\'"]([^\'"]+)[\'"]/', $match, $matches)) {
                $columns[] = $matches[1];
            }
        }

        return $columns;
    }

    /**
     * Apply operations to the create migration
     */
    protected function applyOperationsToCreateMigration(string $content, array $operations, string $migrationType): ?string
    {
        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'add':
                    $content = $this->addColumnToMigration($content, $operation['definition']);
                    break;
                case 'drop':
                    $content = $this->removeColumnFromMigration($content, $operation['column']);
                    break;
                case 'rename':
                    $content = $this->renameColumnInMigration($content, $operation['from'], $operation['to']);
                    break;
            }
        }

        return $content;
    }

    /**
     * Add a column to the create migration
     */
    protected function addColumnToMigration(string $content, string $columnDefinition): string
    {
        // Check if column already exists in the content
        if (preg_match('/\$table->\w+\([\'"]([^\'"]+)[\'"]/', $columnDefinition, $matches)) {
            $columnName = $matches[1];
            // Regex to find existing column definition: $table->method('columnName'
            $pattern = '/\$table->\w+\([\'"]' . preg_quote($columnName, '/') . '[\'"]/';
            if (preg_match($pattern, $content)) {
                return $content;
            }
        }

        // Find the Schema::create block and add the column before timestamps or the closing brace
        $pattern = '/(Schema::create\(.*?function.*?\{[\s\S]*?)(\$table->timestamps\(\);|}\);)/';

        if (preg_match($pattern, $content, $matches)) {
            $before = $matches[1];
            $after = $matches[2];

            // Detect the indentation from existing $table-> statements
            $indentation = $this->detectIndentation($content);

            // Clean up the column definition (remove any leading/trailing whitespace)
            $columnDefinition = trim($columnDefinition);

            // Add proper indentation
            $indentedDefinition = $indentation . $columnDefinition;

            $replacement = $before . $indentedDefinition . "\n" . $indentation . $after;

            return preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    /**
     * Detect the indentation level used in the migration file
     */
    protected function detectIndentation(string $content): string
    {
        // Look for existing $table-> statements to detect indentation
        if (preg_match('/^(\s+)\$table->/m', $content, $matches)) {
            return $matches[1];
        }

        // Default to 12 spaces (3 levels of 4-space indentation)
        return '            ';
    }

    /**
     * Remove a column from the create migration
     */
    protected function removeColumnFromMigration(string $content, string $columnName): string
    {
        // Match the line that defines this column
        $pattern = '/\s*\$table->[a-zA-Z]+\([\'"]' . preg_quote($columnName, '/') . '[\'"].*?\);.*?\n/';

        return preg_replace($pattern, '', $content);
    }

    /**
     * Rename a column in the create migration
     */
    protected function renameColumnInMigration(string $content, string $oldName, string $newName): string
    {
        // Replace the column name in the definition
        $pattern = '/(\$table->[a-zA-Z]+\()[\'"]' . preg_quote($oldName, '/') . '([\'"])/';

        return preg_replace($pattern, '${1}\'' . $newName . '${2}', $content);
    }
}
