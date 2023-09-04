<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Seeders\Traits;

use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\AttributesTrait;
use Tgozo\LaravelCodegen\Console\FakerGuesser;

trait MethodsTrait
{
    use AttributesTrait;
    public function get_seeder_string($modelName, $fields): string
    {
        if (empty($fields)){
            return '//';
        }
        $str = "{$modelName}::insert([" . PHP_EOL;
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
        $str .= "\t\t]);";
        return $str;
    }

    public function create_seeder($modelName, $fields = []): void
    {
        $seeder_name = $modelName . 'Seeder';
        $database_seeder_file = database_path('seeders') . "/{$seeder_name}.php";
        $function_name = 'run';
        $new_code = $this->get_seeder_string($modelName, $fields);

        $name_space = "Database\Seeders";

        $stub = $this->load_stub('seeder.stub');

        $stub = str_replace("{{ namespace }}", $name_space, $stub);

        $stub = str_replace("{{ model }}", $modelName, $stub);

        $stub = str_replace("{{ class }}", $seeder_name, $stub);

        $function_pos = strpos($stub, "function $function_name");

        $open_brace_pos = strpos($stub, '{', $function_pos);

        $new_file_contents = substr_replace($stub, PHP_EOL . "\t\t$new_code", $open_brace_pos + 1, 0);

        file_put_contents($database_seeder_file, $new_file_contents);

        $this->info("Created Seeder {$seeder_name} at {$database_seeder_file}");
        $this->register_seeder($modelName);
    }

    public function register_seeder($modelName): void
    {
        $seeder_name = $modelName . 'Seeder';
        $file_name = 'DatabaseSeeder';
        $database_seeder_file = database_path('seeders') . '/' . $file_name . '.php';
        $function_name = 'run';
        $new_code = "\$this->call({$seeder_name}::class);";

        $file_contents = file_get_contents($database_seeder_file);

        $function_pos = strpos($file_contents, "function $function_name");

        $open_brace_pos = strpos($file_contents, '{', $function_pos);

        $new_file_contents = substr_replace($file_contents, PHP_EOL . "\t\t$new_code", $open_brace_pos + 1, 0);

        file_put_contents($database_seeder_file, $new_file_contents);

        $this->info("Registered Seeder {$seeder_name} in {$file_name} file at {$database_seeder_file}");
    }
}
