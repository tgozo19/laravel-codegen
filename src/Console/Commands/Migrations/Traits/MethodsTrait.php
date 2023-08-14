<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait MethodsTrait
{
    public function getStubName($migrationName)
    {
        return $this->stub_names[$this->checkStart($migrationName)];
    }

    public function getFieldNamesString($fields): string
    {
        if (empty($fields)) return "''";
        if (count($fields) === 1) return "'{$fields[0]['name']}'";
        $callback = function ($field){
            return "'{$field['name']}'";
        };
        return "[" . join(',', array_map(fn() => $callback, $fields)) . "]";
    }

    public function get_migration_description(mixed $migrationType, $fields, string $tableName, $migrationName): mixed
    {
        if (empty($fields)) return $migrationName;

        $callback = function ($field) {
            return "{$field['name']}";
        };

        if ($migrationType === "add_column") {
            $column = $fields[0]['name'];
            return "add_column_{$column}_to_{$tableName}_table";
        }

        if ($migrationType === "add_columns") {
            $columns = join('-', array_map(fn() => $callback, $fields));
            return "add_columns_{$columns}_to_{$tableName}_table";
        }

        return $migrationName;
    }

    public function createMigration($migrationName, $fields, $migrationType = "create"): void
    {
        $tableName = $this->getTableName($migrationName);

        $stubName = $this->getStubName($migrationName);
        $codegen_path = $this->codegen_path("stubs/migration.{$stubName}.stub");

        $stub = file_get_contents($codegen_path);

        $stub = str_replace('{{ tableName }}', $tableName, $stub);

        $fieldsString = $this->getFieldsString($fields);

        $stub = str_replace('{{ fields }}', $fieldsString, $stub);

        if ($migrationType === "add_column"){
            $dropFieldsString = $this->getFieldNamesString($fields);
            $stub = str_replace('{{ dropFields }}', $dropFieldsString, $stub);
        }

        $file_name = $this->get_migration_description($migrationType, $fields, $tableName, $migrationName);

        $migrationFile = database_path('migrations') . '/' . date('Y_m_d_His') . '_' . $file_name . '.php';


        file_put_contents($migrationFile, $stub);
    }
}
