<?php

namespace Tgozo\LaravelCodegen\Console\Commands;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Traits\AttributesTrait;
use Tgozo\LaravelCodegen\Traits\PathTrait;

class MigrationBaseGenerator extends Command
{
    use PathTrait, AttributesTrait;

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
        if (empty($options)) return [];
        $not_valid = [];
        foreach ($options as $option) {
            if (strlen($option) === 0) continue;
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
        if (empty($options) || gettype($options) === 'string') return [];
        $option_values = [];
        foreach ($options as $value) {
            if (strlen($value) === 0) continue;
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

    public function validate_option_values($type, $options): array
    {
        if (empty($options)) return [];
        // value can't be empty
        // correct data type for column
        $invalid_values = [];

        foreach ($options as $option) {
            $position = strpos($option, ":");
            if ($position === false) continue;
            $optionName = substr($option, 0, $position);
            $optionValue = substr($option, $position + 1);

            // check if option has action
            $action = $this->option_actions[$optionName] ?? false;
            if (!$action) continue;
            if ($action === 'take_value'){
                // check the type
                if (in_array($type, $this->numberTypes)){
                    if ($optionName === 'default'){
                        if (!is_numeric($optionValue)){
                            $invalid_values[$optionName] = "should be numeric";
                        }
                    }
                }
                if ($type === 'boolean'){
                    if ($optionName === 'default'){
                        if (gettype($optionValue) !== "boolean"){
                            $invalid_values[$optionName] = "should be a boolean";
                        }
                    }
                }
            }
        }
        return $invalid_values;
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

            if (empty($name)){
                break;
            }

            $has_suggestions = array_key_exists($name, $this->common_columns);
            if ($has_suggestions){
                $type = $this->choice('Select the type of the field?', [...$this->common_columns[$name], "Other"]);
                if ($type === 'Other'){
                    $type = $this->ask('What is the type of the field?');
                }
            }else{
                $type = $this->ask('What is the type of the field?');
            }

            while (!in_array($type, $this->types)) {
                $this->error("The {$type} type is not valid!. Accepted types are: " . implode(', ', $this->types) . ".");
                $type = $this->ask('What is the type of the field?');
            }

            $fields[$index] = [
                'name' => $name,
                'type' => $type,
                'autoIncrement' => false,
                'nullable' => false,
                'default' => '',
                'first' => false,
                'unique' => false,
                'after' => '',
                'charset' => '',
                'collation' => '',
                'comment' => '',
                'storedAs' => '',
                'unsigned' => false,
                'useCurrent' => false,
                'useCurrentOnUpdate' => false,
                'virtualAs' => false,
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
            $invalid_values = $this->validate_option_values($fields[$index]['type'], $options);
            while(!empty($incompatible_array) || !empty($invalid_values)){
                $array_keys = array_keys($incompatible_array);
                foreach ($array_keys as $array_key) {
                    $this->error("The {$array_key} option is not compatible with the " . implode(', ', $incompatible_array[$array_key]) . " option.");
                }

                foreach ($invalid_values as $invalid_key => $invalid_value) {
                    $this->error("Value for {$invalid_key} {$invalid_value}");
                }

                $options_response = $this->ask("Specify the options again. Options should be comma seperated eg. nullable,default:true ");
                $options = explode(",", $options_response);

                $invalid_options = $this->validate_options($options, $this->valid_options);
                while(!empty($invalid_options)){
                    $options_response = $this->ask("Specify any other options. Options should be comma seperated eg. nullable,default:true ");
                    $options = explode(",", $options_response);
                    $invalid_options = $this->validate_options($options, $this->valid_options);
                }

                $incompatible_array = $this->checkForCompatibleOptions($options);
                $invalid_values = $this->validate_option_values($fields[$index]['type'], $options);
            }

            $options = empty($options) ? [] : $options;

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

    public function getFieldsString($fields): string
    {
        $fieldsString = '';

        $fields_count = count($fields);

        foreach ($fields as $index => $field) {
            $fieldsString .= "\$table->{$field['type']}('{$field['name']}')";

            if ($field['autoIncrement']) {
                $fieldsString .= '->autoIncrement()';
            }

            if ($field['nullable']) {
                $fieldsString .= '->nullable()';
            }

            if (!empty($field['default'])) {
                if ($field['type'] !== 'boolean' && !in_array($field['type'], $this->numberTypes)) {
                    $field['default'] = "'" . $field['default'] . "'";
                }
                $fieldsString .= "->default({$field['default']})";
            }

            if ($field['first']) {
                $fieldsString .= '->first()';
            }

            if ($field['useCurrentOnUpdate']) {
                $fieldsString .= '->useCurrentOnUpdate()';
            }

            if (!empty($field['collation'])) {
                $fieldsString .= "->collation('{$field['collation']}')";
            }

            if (!empty($field['charset'])) {
                $fieldsString .= "->charset('{$field['charset']}')";
            }

            if (!empty($field['comment'])) {
                $fieldsString .= "->comment('{$field['comment']}')";
            }

            if ($field['unsigned']) {
                $fieldsString .= '->unsigned()';
            }

            if ($field['useCurrent']) {
                $fieldsString .= '->useCurrent()';
            }

            if (!empty($field['storedAs'])) {
                $fieldsString .= "->storedAs('{$field['storedAs']}')";
            }

            if (!empty($field['virtualAs'])) {
                $fieldsString .= "->virtualAs('{$field['virtualAs']}')";
            }

            if ($field['unique']) {
                $fieldsString .= '->unique()';
            }

            if (!empty($field['after'])) {
                $fieldsString .= "->after('{$field['after']}')";
            }

            if ($field['first']) {
                $fieldsString .= '->first()';
            }

            $fieldsString .= ';';

            if($index !== $fields_count - 1){
                $fieldsString .=  PHP_EOL . "\t\t\t";
            }
        }

        return $fieldsString;
    }

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
