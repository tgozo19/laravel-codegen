<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Factories\Traits;

use Tgozo\LaravelCodegen\Console\FakerGuesser;

trait MethodsTrait
{
    public function get_factory_string($modelName, $fields): string
    {
        if (empty($fields)){
            return '//';
        }
        $str = "return [" . PHP_EOL;
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $guessed_output = FakerGuesser::guess($name, $type);
            $guess = $guessed_output[0];

            if ($name === 'password' or str($name)->contains('password')){
                $str .= "\t\t\t'{$name}' => '{$guess}', // {$guessed_output[1]}";
            }else{
                $str .= "\t\t\t'{$name}' => $guess,";
            }

            $str .= PHP_EOL;
        }
        $str .= "\t\t];";
        return $str;
    }

    public function get_unverified_string($fields): string
    {
        if (empty($fields)){
            return '//';
        }
        $str = "";
        $found = false;
        foreach ($fields as $field) {
            $name = $field['name'];

            if (!str($name)->contains('verified_at')) continue;

            if (!$found){
                $found = true;
                $str .= "'{$name}' => null,";
            }else{
                $str .= PHP_EOL . "\t\t\t\t'{$name}' => null,";
            }
        }

        return $str;
    }

    public function findClosingBrace($str, $pos) {
        if ($str[$pos] == '{') {
            return $this->findClosingBrace($str, $this->findClosingBrace($str, $pos + 1) + 1);
        } elseif ($str[$pos] == '}') {
            return $pos;
        } else {
            return $this->findClosingBrace($str, $pos + 1);
        }
    }

    public function register_factory($modelName): void
    {
        $factory_name = $modelName . "Factory";
        $file_to_use = null;
        $seeder_name = $modelName . 'Seeder';
        $seeder_file = database_path('seeders') . "/{$seeder_name}.php";
        $seeder_exists = file_exists($seeder_file);

        if ($seeder_exists) {
            // register factory in seeder
            $file_to_use = $seeder_file;
        }
        if ($file_to_use === null){
            // register factory in database seeder
            $database_seeder_file = database_path('seeders') . "/DatabaseSeeder.php";
            $database_seeder_exists = file_exists($database_seeder_file);
            if ($database_seeder_exists){
                $file_to_use = $database_seeder_file;
            }
        }

        if ($file_to_use === null){
            return;
        }

        $function_name = 'run';
        $new_code = "{$modelName}::factory(10)->create();" . PHP_EOL . "\t";
        $file_contents = file_get_contents($file_to_use);
        $function_pos = strpos($file_contents, "function $function_name");
        $open_brace_pos = strpos($file_contents, '{', $function_pos);

        $close_brace_pos = $this->findClosingBrace($file_contents, $open_brace_pos + 1);

        $new_file_contents = substr_replace($file_contents, "\t$new_code", $close_brace_pos, 0);
        file_put_contents($file_to_use, $new_file_contents);

        $this->info("Factory [$factory_name] registered successfully in {$file_to_use}.");
    }

    public function create_factory($modelName, $fields = []): void
    {
        $factory_name = $modelName . 'Factory';
        $factory_file = database_path('factories') . "/{$factory_name}.php";
        $function_name = 'definition';
        $new_code = $this->get_factory_string($modelName, $fields);

        $name_space = "Database\Factories";
        $name_spaced_model = "use App\Models\\{$modelName};";

        $stub = $this->load_stub('factory');

        $stub = str_replace("{{ namespace }}", $name_space, $stub);

        $stub = str_replace("{{ namespacedModel }}", $name_spaced_model, $stub);

        $stub = str_replace("{{ str_namespace }}", "", $stub);

        $stub = str_replace("{{ model }}", $modelName, $stub);

        $stub = str_replace("{{ factory }}", $factory_name, $stub);

        $function_pos = strpos($stub, "function $function_name");

        $open_brace_pos = strpos($stub, '{', $function_pos);

        $stub = substr_replace($stub, PHP_EOL . "\t\t$new_code", $open_brace_pos + 1, 0);

        $contains_verified_at = !empty(array_filter($fields, function ($field){
            return str($field['name'])->contains('verified_at');
        }));

        if ($contains_verified_at){
            $unverified = $this->get_unverified_string($fields);
            $stub = str_replace("{{ unverified }}", $unverified, $stub);
        }else{
            $stub = str_replace("{{ unverified }}", "//", $stub);
        }

        file_put_contents($factory_file, $stub);

        $this->info("Factory [$factory_file] created successfully.");
        $this->register_factory($modelName);
    }
}
