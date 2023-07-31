<?php

namespace Tgozo\CodeGenerator\Console\Commands;

use Illuminate\Console\Command;

class MigrationBaseGenerator extends Command
{
    private array $types = [
        'string', 'text', 'integer', 'bigInteger', 'unsignedBigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'decimal', 'unsignedDecimal', 'float', 'double', 'boolean', 'enum', 'json', 'jsonb', 'date', 'dateTime', 'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'year', 'binary', 'uuid', 'ipAddress', 'macAddress'
    ];
    private array $numberTypes = [
        'integer', 'bigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger'
    ];

    private array $patterns = [
        'start' => ['create_'],
        'ending' => [
            'create_' => ['_table']
        ]
    ];

    private array $valid_options = ['nullable', 'default'];

    private array $option_actions = ['nullable' => true, 'default' => 'take_value'];

    private array $incompatible_options = [
        'nullable' => ['default'],
        'default' => ['nullable'],
        'primary_key' => ['nullable', 'default'],
        'unique' => ['nullable', 'default'],
        'index' => ['nullable', 'default'],
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

        $this->followsPattern($name);

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

    private function validate_options($options, $valid_options): array
    {
        if (count($options)) return [];
        $not_valid = [];
        foreach ($options as $option) {
            $position = strpos($option, ":");
            if ($position !== false){
                $option = substr($option, 0, $position);
            }

            if (!in_array($option, $valid_options)){
                $not_valid[] = $option;
                $this->error("The option {$option} is not valid");
            }
        }
        return $not_valid;
    }

    private function get_option_values($options, $valid_options): array
    {
        if (count($options)) return [];
        $option_values = [];
        foreach ($options as $value) {
            $position = strpos($value, ":");
            if ($position !== false){
                $key = substr($value, 0, $position);
                $value = substr($value, $position + 1);
            }else{
                $key = $value;
                $value = true;
            }

            $option_values[$key] = $value;
        }
        return $option_values;
    }

    public function checkForCompatibleOptions($options): array
    {
        $incompatible_array = [];
        $specified_options = [];
        // get all specified options
        foreach ($options as $option) {
            $position = strpos($option, ":");
            if ($position !== false){
                $option = substr($option, 0, $position);
            }

            $specified_options[] = $option;
        }

        foreach ($options as $option) {
            $position = strpos($option, ":");
            if ($position !== false){
                $option = substr($option, 0, $position);
            }

            if (array_key_exists($option, $this->incompatible_options)) {
                $incompatible_options = $this->incompatible_options[$option];

                foreach ($incompatible_options as $incompatible_option) {
                    if (in_array($incompatible_option, $specified_options)) {
                        $incompatible_array[$option][] = $incompatible_option;
                        break;
                    }
                }
            }
        }

        return $incompatible_array;
    }

    public function getFields(): array
    {
        $fields = [];
        $name = $this->ask('Specify a field name (or press <return> to stop adding fields)');

        $index = 0;
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

            $fields[$index] = [
                'name' => $name,
                'type' => $type,
                'autoIncrement' => $autoIncrement,
                'nullable' => false,
                'default' => ''
            ];

            // ask for options
            $options_response = $this->ask("Specify any other options. Options should be comma seperated eg. nullable,default:true ");
            $options = explode(",", $options_response);

            $invalid_options = $this->validate_options($options, $this->valid_options);
            while(!empty($invalid_options)){
                $options_response = $this->ask("Specify any other options. Options should be comma seperated eg. nullable,default:true ");
                $options = explode(",", $options_response);
                $invalid_options = $this->validate_options($options, $this->valid_options);
            }

            // check among the provided options if there are options which are not compatible with each other, e.g. nullable and default and raise error
            $incompatible_array = $this->checkForCompatibleOptions($options);
            while(!empty($incompatible_array)){
                $array_keys = array_keys($incompatible_array);
                foreach ($array_keys as $array_key) {
                    $this->error("The {$array_key} option is not compatible with the " . implode(', ', $incompatible_array[$array_key]) . " option.");
                }
                $options_response = $this->ask("Specify any other options. Options should be comma seperated eg. nullable,default:true ");
                $options = explode(",", $options_response);
                $invalid_options = $this->validate_options($options, $this->valid_options);
                $incompatible_array = $this->checkForCompatibleOptions($options);
            }

            $valid_options = $this->get_option_values($options, $this->valid_options);

            foreach ($valid_options as $key => $valid_option) {
                $action = $this->option_actions[$key];
                if ($action === true){
                    $fields[$index][$key] = true;
                }

                if ($action === "take_value"){
                    $fields[$index][$key] = $valid_option;
                }
            }

//            if ($nullable) {
//                $default = null;
//            } else {
//                // if type is boolean, the accepted value should either be true or false
//                if ($type === 'boolean') {
//                    $default = $this->ask('What is the default value of the field? (true/false)');
//                    while (!in_array($default, ['true', 'false'])) {
//                        $this->error("The {$default} value is not valid!. Accepted values are: true, false.");
//                        $default = $this->ask('What is the default value of the field? (true/false)');
//                    }
//                } else {
//                    $default = $this->ask('What is the default value of the field?');
//                }
//            }

            $name = $this->ask('Specify a field name (or press <return> to stop adding fields)');
            $index++;
        }

        return $fields;
    }

    public function checkStart($name)
    {
        foreach ($this->patterns['start'] as $pattern) {
            $starts_with = str($name)->startsWith($pattern);
            if ($starts_with){
                return $pattern;
            }
        }
        return null;
    }

    public function checkEnding($key, $name)
    {
        foreach ($this->patterns['ending'][$key] as $pattern) {
            $ends_with = str($name)->endsWith($pattern);
            if ($ends_with){
                return $pattern;
            }
        }
        return null;
    }

    public function hasExpectedEnding($pattern): bool
    {
        return array_key_exists($pattern, $this->patterns['ending']);
    }

    public function create_validate($starts_with, $final_table_name): string
    {
        $final_table_name = str_replace($starts_with, "", $final_table_name);

        $has_ending_pattern = $this->hasExpectedEnding($starts_with);
        if ($has_ending_pattern){
            $ends_with = $this->checkEnding($starts_with, $final_table_name);
            if ($ends_with === null){
                $this->error("A migration which starts with {$starts_with} declarative should end with " . implode(', ', $this->patterns['ending'][$starts_with]));
                exit;
            }
            $final_table_name = str_replace($ends_with, "", $final_table_name);
        }
        if (strlen($final_table_name) === 0){
            $this->error("Please provide a valid table name");
            exit;
        }

        return $final_table_name;
    }

    public function followsPattern($name): void
    {
        $starts_with = $this->checkStart($name);

        if ($starts_with === null){
            $this->error("The migration name should start with any of the following declarative " . implode(', ', $this->patterns['start']));
            exit;
        }

        if (!method_exists($this,$starts_with . "validate")){
            $this->error("Support for the declarative {$starts_with} is not yet implemented");
            exit;
        }

        $this->{$starts_with . "validate"}($starts_with, $name);
    }

    public function getTableName($name): string
    {
        $starts_with = $this->checkStart($name);

        return $this->{$starts_with . "validate"}($starts_with, $name);
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
                // if type is boolean, remove quotes from default value
                if ($field['type'] == 'boolean') {
                    $field['default'] = str_replace("'", '', $field['default']);
                }else{
                    $fieldsString .= "->default('{$field['default']}')";
                }
            }

            $fieldsString .= ';' . PHP_EOL . "\t\t\t";
        }

        $stub = str_replace('{{ fields }}', $fieldsString, $stub);

        file_put_contents($migrationFile, $stub);
    }
}
