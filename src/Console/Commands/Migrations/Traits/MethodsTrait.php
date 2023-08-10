<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait MethodsTrait
{
    public function getStubName($migrationName)
    {
        return $this->stub_names[$this->checkStart($migrationName)];
    }
    public function createMigration($migrationName, $fields): void
    {
        $tableName = $this->getTableName($migrationName);

        $migrationFile = database_path('migrations') . '/' . date('Y_m_d_His') . '_' . $migrationName . '.php';

        $stubName = $this->getStubName($migrationName);
        $codegen_path = $this->codegen_path("stubs/migration.{$stubName}.stub");

        $stub = file_get_contents($codegen_path);

        $stub = str_replace('{{ tableName }}', $tableName, $stub);

        $fieldsString = $this->getFieldsString($fields);

        $stub = str_replace('{{ fields }}', $fieldsString, $stub);

        file_put_contents($migrationFile, $stub);
    }
}
