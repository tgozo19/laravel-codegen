<?php

namespace Tgozo\LaravelCodegen\Console\Commands\ReverseEngineer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tgozo\LaravelCodegen\Console\BaseTrait;

class ReverseEngineerCommand extends Command
{
    use BaseTrait;

    protected $signature = 'codegen:reverse-engineer 
                            {--tables=* : Specific tables to reverse engineer (leave empty for all tables)}
                            {--models : Generate models from existing tables}
                            {--migrations : Generate migrations from existing tables}
                            {--all : Generate both models and migrations}
                            {--force : Overwrite existing files}
                            {--dry-run : Simulate reverse engineering without writing files}
                            {--connection= : Database connection to use}';

    protected $description = 'Reverse engineer existing database tables to generate models and/or migrations';

    protected array $tableSchemas = [];
    protected array $foreignKeys = [];
    protected string $connection;

    public function handle(): void
    {
        $this->connection = $this->option('connection') ?? config('database.default');
        
        if (!$this->option('models') && !$this->option('migrations') && !$this->option('all')) {
            $this->error('You must specify either --models, --migrations, or --all');
            return;
        }

        $tables = $this->getTargetTables();
        
        if (empty($tables)) {
            $this->error('No tables found to reverse engineer');
            return;
        }

        $this->info("Found " . count($tables) . " table(s) to reverse engineer");

        // Analyze all tables first
        $this->analyzeTableSchemas($tables);
        
        // Generate files
        if ($this->option('models') || $this->option('all')) {
            $this->generateModels($tables);
        }
        
        if ($this->option('migrations') || $this->option('all')) {
            $this->generateMigrations($tables);
        }

        $this->info('Reverse engineering completed successfully!');
    }

    protected function getTargetTables(): array
    {
        $specifiedTables = $this->option('tables');
        
        if (!empty($specifiedTables)) {
            // Validate specified tables exist
            $existingTables = Schema::connection($this->connection)->getTableListing();
            $validTables = array_intersect($specifiedTables, $existingTables);
            
            $invalidTables = array_diff($specifiedTables, $validTables);
            if (!empty($invalidTables)) {
                $this->warn('The following tables do not exist: ' . implode(', ', $invalidTables));
            }
            
            return $validTables;
        }

        // Get all tables excluding Laravel system tables
        $allTables = Schema::connection($this->connection)->getTableListing();
        $excludedTables = [
            'migrations',
            'password_resets',
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens',
            'jobs',
            'job_batches'
        ];

        return array_diff($allTables, $excludedTables);
    }

    protected function analyzeTableSchemas(array $tables): void
    {
        $this->info('Analyzing table schemas...');
        
        foreach ($tables as $table) {
            $this->tableSchemas[$table] = $this->getTableSchema($table);
            $this->foreignKeys[$table] = $this->getForeignKeys($table);
        }
    }

    protected function getTableSchema(string $table): array
    {
        $schemaBuilder = Schema::connection($this->connection);

        if (method_exists($schemaBuilder, 'getColumns')) {
            try {
                $nativeColumns = $schemaBuilder->getColumns($table);
                $columnDetails = [];
                foreach ($nativeColumns as $col) {
                    $name = $col['name'];
                    $typeName = strtolower($col['type_name'] ?? $col['type'] ?? 'string');
                    $columnDetails[$name] = [
                        'type' => $this->mapInformationSchemaType($typeName),
                        'length' => $col['length'] ?? null,
                        'precision' => $col['precision'] ?? null,
                        'scale' => $col['scale'] ?? null,
                        'nullable' => $col['nullable'] ?? true,
                        'default' => $col['default'] ?? null,
                        'autoIncrement' => $col['auto_increment'] ?? false,
                        'unsigned' => $col['unsigned'] ?? false,
                    ];
                }
                return $columnDetails;
            } catch (\Throwable $e) {
                // Fall back to manual driver queries if native call fails
            }
        }

        $columns = $schemaBuilder->getColumnListing($table);
        $columnDetails = [];

        foreach ($columns as $column) {
            try {
                // Use raw SQL queries to get column information
                $columnInfo = $this->getColumnInfo($table, $column);
                $columnDetails[$column] = $columnInfo;
            } catch (\Exception $e) {
                $this->warn("Could not get details for column {$column} in table {$table}: " . $e->getMessage());
                // Fallback with basic info
                $columnDetails[$column] = [
                    'type' => 'string',
                    'length' => null,
                    'precision' => null,
                    'scale' => null,
                    'nullable' => true,
                    'default' => null,
                    'autoIncrement' => false,
                    'unsigned' => false,
                ];
            }
        }

        return $columnDetails;
    }

    protected function getColumnInfo(string $table, string $column): array
    {
        $connection = DB::connection($this->connection);
        $driverName = $connection->getDriverName();
        
        switch ($driverName) {
            case 'mysql':
                return $this->getMySqlColumnInfo($connection, $table, $column);
            case 'pgsql':
                return $this->getPostgreSqlColumnInfo($connection, $table, $column);
            case 'sqlite':
                return $this->getSqliteColumnInfo($connection, $table, $column);
            default:
                throw new \Exception("Database driver '{$driverName}' is not supported for reverse engineering");
        }
    }

    protected function getMySqlColumnInfo($connection, string $table, string $column): array
    {
        $database = $connection->getDatabaseName();
        
        $columnInfo = $connection->selectOne("
            SELECT 
                COLUMN_NAME,
                DATA_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                CHARACTER_MAXIMUM_LENGTH,
                NUMERIC_PRECISION,
                NUMERIC_SCALE,
                EXTRA,
                COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ", [$database, $table, $column]);

        if (!$columnInfo) {
            throw new \Exception("Column information not found");
        }

        return [
            'type' => $this->mapInformationSchemaType($columnInfo->DATA_TYPE),
            'length' => $columnInfo->CHARACTER_MAXIMUM_LENGTH,
            'precision' => $columnInfo->NUMERIC_PRECISION,
            'scale' => $columnInfo->NUMERIC_SCALE,
            'nullable' => $columnInfo->IS_NULLABLE === 'YES',
            'default' => $columnInfo->COLUMN_DEFAULT === 'NULL' ? null : $columnInfo->COLUMN_DEFAULT,
            'autoIncrement' => str_contains(strtolower($columnInfo->EXTRA ?? ''), 'auto_increment'),
            'unsigned' => str_contains(strtolower($columnInfo->COLUMN_TYPE ?? ''), 'unsigned'),
        ];
    }

    protected function getPostgreSqlColumnInfo($connection, string $table, string $column): array
    {
        $schema = $connection->getConfig('search_path') ?? 'public';
        
        $columnInfo = $connection->selectOne("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length,
                numeric_precision,
                numeric_scale,
                udt_name
            FROM information_schema.columns 
            WHERE table_schema = ? AND table_name = ? AND column_name = ?
        ", [$schema, $table, $column]);

        if (!$columnInfo) {
            throw new \Exception("Column information not found");
        }

        $isSerial = str_contains($columnInfo->column_default ?? '', 'nextval');

        return [
            'type' => $this->mapPostgreSqlType($columnInfo->data_type, $columnInfo->udt_name),
            'length' => $columnInfo->character_maximum_length,
            'precision' => $columnInfo->numeric_precision,
            'scale' => $columnInfo->numeric_scale,
            'nullable' => $columnInfo->is_nullable === 'YES',
            'default' => $isSerial ? null : $columnInfo->column_default,
            'autoIncrement' => $isSerial,
            'unsigned' => false, // PostgreSQL doesn't have unsigned types
        ];
    }

    protected function getSqliteColumnInfo($connection, string $table, string $column): array
    {
        $tableInfo = $connection->select("PRAGMA table_info({$table})");
        
        foreach ($tableInfo as $columnInfo) {
            if ($columnInfo->name === $column) {
                return [
                    'type' => $this->mapSqliteType($columnInfo->type),
                    'length' => null,
                    'precision' => null,
                    'scale' => null,
                    'nullable' => !$columnInfo->notnull,
                    'default' => $columnInfo->dflt_value,
                    'autoIncrement' => $columnInfo->pk && str_contains(strtolower($columnInfo->type), 'integer'),
                    'unsigned' => false,
                ];
            }
        }
        
        throw new \Exception("Column information not found");
    }

    protected function mapInformationSchemaType(string $type): string
    {
        return match (strtolower($type)) {
            'tinyint' => 'tinyint',
            'smallint' => 'smallint', 
            'mediumint' => 'integer',
            'int', 'integer' => 'integer',
            'bigint' => 'bigint',
            'decimal', 'numeric' => 'decimal',
            'float' => 'float',
            'double', 'real' => 'double',
            'bit', 'boolean', 'bool' => 'boolean',
            'char' => 'string',
            'varchar' => 'string',
            'tinytext', 'text' => 'text',
            'mediumtext' => 'mediumtext',
            'longtext' => 'longtext',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'year' => 'integer',
            'json' => 'json',
            'binary', 'varbinary' => 'binary',
            'blob', 'tinyblob', 'mediumblob', 'longblob' => 'binary',
            default => 'string'
        };
    }

    protected function mapPostgreSqlType(string $dataType, string $udtName): string
    {
        return match (strtolower($dataType)) {
            'smallint' => 'smallint',
            'integer' => 'integer', 
            'bigint' => 'bigint',
            'decimal', 'numeric' => 'decimal',
            'real' => 'float',
            'double precision' => 'double',
            'boolean' => 'boolean',
            'character', 'char' => 'string',
            'character varying', 'varchar' => 'string',
            'text' => 'text',
            'date' => 'date',
            'time without time zone', 'time with time zone' => 'time',
            'timestamp without time zone', 'timestamp with time zone' => 'timestamp',
            'json', 'jsonb' => 'json',
            'bytea' => 'binary',
            'uuid' => 'string',
            default => match (strtolower($udtName)) {
                'int2' => 'smallint',
                'int4' => 'integer',
                'int8' => 'bigint',
                'float4' => 'float',
                'float8' => 'double',
                'bool' => 'boolean',
                'varchar', 'text' => 'string',
                'json', 'jsonb' => 'json',
                default => 'string'
            }
        };
    }

    protected function mapSqliteType(string $type): string
    {
        $type = strtolower(trim($type));
        
        if (str_contains($type, 'int')) {
            return 'integer';
        }
        
        if (str_contains($type, 'char') || str_contains($type, 'text')) {
            return str_contains($type, 'text') ? 'text' : 'string';
        }
        
        if (str_contains($type, 'real') || str_contains($type, 'double') || str_contains($type, 'float')) {
            return 'float';
        }
        
        if (str_contains($type, 'decimal') || str_contains($type, 'numeric')) {
            return 'decimal';
        }
        
        if (str_contains($type, 'date')) {
            return 'date';
        }
        
        if (str_contains($type, 'time')) {
            return 'datetime';
        }
        
        if (str_contains($type, 'bool')) {
            return 'boolean';
        }
        
        return 'string';
    }

    protected function getForeignKeys(string $table): array
    {
        $schemaBuilder = Schema::connection($this->connection);

        if (method_exists($schemaBuilder, 'getForeignKeys')) {
            try {
                $nativeFKs = $schemaBuilder->getForeignKeys($table);
                $foreignKeys = [];
                foreach ($nativeFKs as $fk) {
                    $localColumn = is_array($fk['columns'] ?? null) ? ($fk['columns'][0] ?? null) : ($fk['columns'] ?? null);
                    $foreignTable = $fk['foreign_table'] ?? null;
                    $foreignColumn = is_array($fk['foreign_columns'] ?? null) ? ($fk['foreign_columns'][0] ?? null) : ($fk['foreign_columns'] ?? null);
                    if ($localColumn && $foreignTable && $foreignColumn) {
                        $foreignKeys[] = [
                            'local_column' => $localColumn,
                            'foreign_table' => $foreignTable,
                            'foreign_column' => $foreignColumn,
                            'constraint_name' => $fk['name'] ?? "fk_{$table}_{$localColumn}"
                        ];
                    }
                }
                return $foreignKeys;
            } catch (\Throwable $e) {
                // Fall back to driver-specific queries if native call fails
            }
        }

        $foreignKeys = [];
        
        try {
            $connection = DB::connection($this->connection);
            $driverName = $connection->getDriverName();
            
            switch ($driverName) {
                case 'mysql':
                    $foreignKeys = $this->getMySqlForeignKeys($connection, $table);
                    break;
                case 'pgsql':
                    $foreignKeys = $this->getPostgreSqlForeignKeys($connection, $table);
                    break;
                case 'sqlite':
                    $foreignKeys = $this->getSqliteForeignKeys($connection, $table);
                    break;
                default:
                    $this->warn("Foreign key detection not supported for database driver: {$driverName}");
            }
        } catch (\Exception $e) {
            $this->warn("Could not retrieve foreign keys for table {$table}: " . $e->getMessage());
        }

        return $foreignKeys;
    }

    protected function getMySqlForeignKeys($connection, string $table): array
    {
        $database = $connection->getDatabaseName();
        
        $constraints = $connection->select("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
        
        $foreignKeys = [];
        foreach ($constraints as $constraint) {
            $foreignKeys[] = [
                'local_column' => $constraint->COLUMN_NAME,
                'foreign_table' => $constraint->REFERENCED_TABLE_NAME,
                'foreign_column' => $constraint->REFERENCED_COLUMN_NAME,
                'constraint_name' => $constraint->CONSTRAINT_NAME
            ];
        }
        
        return $foreignKeys;
    }

    protected function getPostgreSqlForeignKeys($connection, string $table): array
    {
        $schema = $connection->getConfig('search_path') ?? 'public';
        
        $constraints = $connection->select("
            SELECT 
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints tc 
            JOIN information_schema.key_column_usage kcu 
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage ccu 
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_schema = ?
                AND tc.table_name = ?
        ", [$schema, $table]);
        
        $foreignKeys = [];
        foreach ($constraints as $constraint) {
            $foreignKeys[] = [
                'local_column' => $constraint->column_name,
                'foreign_table' => $constraint->foreign_table_name,
                'foreign_column' => $constraint->foreign_column_name,
                'constraint_name' => $constraint->constraint_name
            ];
        }
        
        return $foreignKeys;
    }

    protected function getSqliteForeignKeys($connection, string $table): array
    {
        $foreignKeys = [];
        
        $foreignKeyList = $connection->select("PRAGMA foreign_key_list({$table})");
        
        foreach ($foreignKeyList as $fk) {
            $foreignKeys[] = [
                'local_column' => $fk->from,
                'foreign_table' => $fk->table,
                'foreign_column' => $fk->to,
                'constraint_name' => "fk_{$table}_{$fk->from}"
            ];
        }
        
        return $foreignKeys;
    }

    protected function generateModels(array $tables): void
    {
        $this->info('Generating models...');
        
        foreach ($tables as $table) {
            $this->generateModel($table);
        }
    }

    protected function generateModel(string $table): void
    {
        $modelName = $this->getModelNameFromTable($table);
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (file_exists($modelPath) && !$this->option('force')) {
            $this->warn("Model {$modelName} already exists. Use --force to overwrite.");
            return;
        }

        $modelContent = $this->buildModelContent($table, $modelName);
        
        if (!is_dir(app_path('Models'))) {
            mkdir(app_path('Models'), 0755, true);
        }
        
        file_put_contents($modelPath, $modelContent);
        $this->info("Generated model: {$modelName}");
    }

    protected function buildModelContent(string $table, string $modelName): string
    {
        $stub = $this->load_stub('reverse-engineer.model');
        $columns = $this->tableSchemas[$table];
        $foreignKeys = $this->foreignKeys[$table];
        
        // Build casts array
        $casts = $this->buildCastsArray($columns);
        
        // Build relationships
        $relationships = $this->buildRelationships($table, $foreignKeys);
        
        // Determine if timestamps exist
        $hasTimestamps = isset($columns['created_at']) && isset($columns['updated_at']);

        // Determine if soft deletes exist
        $hasSoftDeletes = isset($columns['deleted_at']);
        $softDeletesImport = $hasSoftDeletes ? "\nuse Illuminate\Database\Eloquent\SoftDeletes;" : '';
        $softDeletesTrait = $hasSoftDeletes ? ", SoftDeletes" : '';
        
        return str_replace([
            '{{modelName}}',
            '{{tableName}}',
            '{{casts}}',
            '{{relationships}}',
            '{{timestamps}}',
            '{{softDeletesImport}}',
            '{{softDeletesTrait}}'
        ], [
            $modelName,
            $table,
            $casts,
            $relationships,
            $hasTimestamps ? 'true' : 'false',
            $softDeletesImport,
            $softDeletesTrait
        ], $stub);
    }


    protected function buildCastsArray(array $columns): string
    {
        $casts = [];
        
        foreach ($columns as $column => $details) {
            $cast = $this->mapColumnTypeToCast($details['type'], $column);
            if ($cast) {
                $casts[] = "        '{$column}' => '{$cast}'";
            }
        }
        
        if (empty($casts)) {
            return '[]';
        }
        
        return "[\n" . implode(",\n", $casts) . "\n    ]";
    }

    protected function mapColumnTypeToCast(string $type, string $column): ?string
    {
        return match ($type) {
            'boolean' => 'boolean',
            'integer', 'bigint', 'smallint' => 'integer',
            'decimal', 'float', 'double' => 'decimal:2',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'time' => 'time',
            'json' => 'array',
            default => Str::contains($column, '_at') ? 'datetime' : null
        };
    }

    protected function buildRelationships(string $table, array $foreignKeys): string
    {
        if (empty($foreignKeys)) {
            return '';
        }
        
        $relationships = [];
        
        foreach ($foreignKeys as $fk) {
            $relationshipName = $this->getRelationshipName($fk['foreign_table']);
            $relatedModel = $this->getModelNameFromTable($fk['foreign_table']);
            
            $relationships[] = "
    public function {$relationshipName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$fk['local_column']}', '{$fk['foreign_column']}');
    }";
        }
        
        // Look for reverse relationships (hasMany)
        foreach ($this->tableSchemas as $otherTable => $schema) {
            if ($otherTable === $table) continue;
            
            $otherForeignKeys = $this->foreignKeys[$otherTable] ?? [];
            foreach ($otherForeignKeys as $fk) {
                if ($fk['foreign_table'] === $table) {
                    $relationshipName = $this->getRelationshipName($otherTable, true);
                    $relatedModel = $this->getModelNameFromTable($otherTable);
                    
                    $relationships[] = "
    public function {$relationshipName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$fk['local_column']}', '{$fk['foreign_column']}');
    }";
                }
            }
        }
        
        return implode('', $relationships);
    }

    protected function getRelationshipName(string $table, bool $plural = false): string
    {
        $name = Str::camel(Str::singular($table));
        return $plural ? Str::plural($name) : $name;
    }

    protected function getModelNameFromTable(string $table): string
    {
        return Str::studly(Str::singular($table));
    }

    protected function generateMigrations(array $tables): void
    {
        $this->info('Generating migrations...');
        
        foreach ($tables as $table) {
            $this->generateMigration($table);
        }
    }

    protected function generateMigration(string $table): void
    {
        $migrationName = "create_{$table}_table";
        $className = Str::studly($migrationName);
        $timestamp = date('Y_m_d_His') . sprintf('%02d', array_search($table, array_keys($this->tableSchemas)));
        $fileName = "{$timestamp}_{$migrationName}.php";
        $migrationPath = database_path("migrations/{$fileName}");
        
        if (!$this->option('force')) {
            $existingMigrations = glob(database_path('migrations/*_' . $migrationName . '.php'));
            if (!empty($existingMigrations)) {
                $this->warn("Migration for table {$table} already exists. Use --force to overwrite.");
                return;
            }
        }

        $migrationContent = $this->buildMigrationContent($table, $className);
        
        if (!is_dir(database_path('migrations'))) {
            mkdir(database_path('migrations'), 0755, true);
        }
        
        file_put_contents($migrationPath, $migrationContent);
        $this->info("Generated migration: {$fileName}");
    }

    protected function buildMigrationContent(string $table, string $className): string
    {
        $stub = $this->load_stub('reverse-engineer.migration');
        $columns = $this->tableSchemas[$table];
        $foreignKeys = $this->foreignKeys[$table];
        
        $schemaDefinition = $this->buildSchemaDefinition($columns, $foreignKeys);
        
        return str_replace([
            '{{className}}',
            '{{tableName}}',
            '{{schemaDefinition}}'
        ], [
            $className,
            $table,
            $schemaDefinition
        ], $stub);
    }

    protected function buildSchemaDefinition(array $columns, array $foreignKeys): string
    {
        $lines = [];
        
        foreach ($columns as $column => $details) {
            $line = $this->buildColumnDefinition($column, $details);
            $lines[] = "            {$line}";
        }
        
        // Add foreign key constraints
        foreach ($foreignKeys as $fk) {
            $lines[] = "            \$table->foreign('{$fk['local_column']}')->references('{$fk['foreign_column']}')->on('{$fk['foreign_table']}');";
        }
        
        return implode("\n", $lines);
    }

    protected function buildColumnDefinition(string $column, array $details): string
    {
        $type = $this->mapDatabaseTypeToMigration($details['type'], $details);
        $definition = "\$table->{$type}('{$column}'";
        
        // Add length for string types
        if (in_array($details['type'], ['string', 'char']) && $details['length']) {
            $definition .= ", {$details['length']}";
        }
        
        // Add precision and scale for decimal types
        if ($details['type'] === 'decimal' && $details['precision'] && $details['scale']) {
            $definition .= ", {$details['precision']}, {$details['scale']}";
        }
        
        $definition .= ')';
        
        // Add modifiers
        if ($details['nullable']) {
            $definition .= '->nullable()';
        }
        
        if ($details['default'] !== null) {
            $defaultValue = is_string($details['default']) ? "'{$details['default']}'" : $details['default'];
            $definition .= "->default({$defaultValue})";
        }
        
        if ($details['unsigned']) {
            $definition .= '->unsigned()';
        }
        
        $definition .= ';';
        
        return $definition;
    }

    protected function mapDatabaseTypeToMigration(string $type, array $details): string
    {
        return match ($type) {
            'integer' => $details['autoIncrement'] ? 'id' : 'integer',
            'bigint' => $details['autoIncrement'] ? 'id' : 'bigInteger',
            'smallint' => 'smallInteger',
            'tinyint' => $details['length'] === 1 ? 'boolean' : 'tinyInteger',
            'decimal' => 'decimal',
            'float' => 'float',
            'double' => 'double',
            'string' => 'string',
            'text' => 'text',
            'longtext' => 'longText',
            'mediumtext' => 'mediumText',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'boolean' => 'boolean',
            'json' => 'json',
            'binary' => 'binary',
            default => 'string'
        };
    }
}