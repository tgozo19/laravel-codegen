<?php

namespace Tgozo\CodeGenerator\Console\Commands;

use Illuminate\Console\Command;

class MigrationBaseGenerator extends Command
{
    protected array $types = [
        'string', 'text', 'integer', 'bigInteger', 'unsignedBigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'decimal', 'unsignedDecimal', 'float', 'double', 'boolean', 'enum', 'json', 'jsonb', 'date', 'dateTime', 'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'year', 'binary', 'uuid', 'ipAddress', 'macAddress'
    ];
    private array $numberTypes = [
        'integer', 'bigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger'
    ];

    public function getMigrationName()
    {
        $name = $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the migration?');
        }

        if (empty($name)) {
            $this->error('The migration name is required!');
            exit;
        }

        return $name;
    }

    public function checkFieldName($fields, $name): bool
    {
        foreach ($fields as $field) {
            if ($field['name'] == $name) {
                return false;
            }
        }

        return true;
    }

    public function getFields(): array
    {
        $fields = [];
        $name = $this->ask('Specify a field name (or press <return> to stop adding fields)');

        while (!empty($name)) {

            while (!$this->checkFieldName($fields, $name)) {
                $this->error("The {$name} field name is already used!");
                $name = $this->ask('Specify a different field name (or press <return> to stop adding fields)');
            }

            $type = $this->ask('What is the type of the field?');

            while (!in_array($type, $this->types)) {
                $this->error("The {$type} type is not valid!. Accepted types are: " . implode(', ', $this->types) . ".");
                $type = $this->ask('What is the type of the field?');
            }

            if (in_array($type, $this->numberTypes)) {
                $autoIncrement = $this->confirm('Is the field auto increment?', false);
            } else {
                $autoIncrement = false;
            }

            if ($autoIncrement) {
                $nullable = false;
            } else {
                $nullable = $this->confirm('Is the field nullable?', false);
            }

            if ($nullable) {
                $default = null;
            } else {
                $default = $this->ask('What is the default value of the field?');
            }

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
                'autoIncrement' => $autoIncrement,
                'default' => $default,
            ];

            $name = $this->ask('Specify a field name (or press <return> to stop adding fields)');
        }

        return $fields;
    }

    // get table_name, return the string table_name if create_ and _table are not found in migrationName

    public function getTableName($migrationName): string
    {
        if (!(str_contains($migrationName, 'create_') && str_contains($migrationName, '_table'))) {
            return 'table_name';
        }
        // extract a valid table name from migrationName
        return str_replace(['create_', '_table'], '', $migrationName);
    }

    public function createMigration($migrationName, $fields): void
    {
        $tableName = $this->getTableName($migrationName);

        $migrationFile = database_path('migrations') . '/' . date('Y_m_d_His') . '_' . $migrationName . '.php';

        $stub = file_get_contents(__DIR__ . '/stubs/migration.create.stub');

        $stub = str_replace('{{ tableName }}', $tableName, $stub);

        $fieldsString = '';

        foreach ($fields as $field) {
            $fieldsString .= "\$table->{$field['type']}('{$field['name']}')";

            if ($field['autoIncrement']) {
                $fieldsString .= '->autoIncrement()';
            }

            if ($field['nullable']) {
                $fieldsString .= '->nullable()';
            }

            if (!empty($field['default'])) {
                $fieldsString .= "->default('{$field['default']}')";
            }

            $fieldsString .= ';' . PHP_EOL . "\t\t\t";
        }

        $stub = str_replace('{{ fields }}', $fieldsString, $stub);

        file_put_contents($migrationFile, $stub);
    }
}
