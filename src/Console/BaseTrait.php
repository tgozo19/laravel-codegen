<?php

namespace Tgozo\LaravelCodegen\Console;

use Doctrine\Inflector\Inflector;
use Illuminate\Support\Facades\Route;

trait BaseTrait
{
    public function codegen_path($path): string
    {
        return dirname(__DIR__, 1) . "/{$path}";
    }

    public function load_stub($name): string
    {
        if (str($name)->endsWith('.stub')){
            $pos = strpos($name, '.stub');
            $name = substr($name, 0, $pos);
        }

        $dir_name = dirname(__DIR__, 1) . "/stubs/{$name}.stub";
        return file_get_contents($dir_name);
    }

    public function snakeToCamelPlural($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return app(Inflector::class)->pluralize($string);
    }

    public function snakeToCamelSingular($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return app(Inflector::class)->singularize($string);
    }

    public function formatFile($file): void
    {
        $contents = file_get_contents($file);
        $contents = preg_replace("/\n\s*\n/", "\n\n", $contents);
        file_put_contents($file, $contents);
    }

    public function singularize($str): string
    {
        return app(Inflector::class)->singularize($str);
    }

    public function pluralize($str): string
    {
        return app(Inflector::class)->pluralize($str);
    }

    public function str_to_lower($str): string
    {
        return strtolower($str);
    }

    public function str_to_upper($str): string
    {
        return strtoupper($str);
    }

    public function intersectArrays($arr1, $arr2): array
    {
        return array_values(array_intersect($arr1, $arr2));
    }

    public function controller_name_from_model($modelName): string
    {
        return $modelName . "Controller";
    }

    public function format_to_get_model_name($str): string
    {
        $str = $this->str_to_lower($str);
        // application_ attachments
        $str = implode('', array_map(function ($a){return $a;}, explode(' ', $str)));
        // application_attachments
        if (str($str)->contains('_')){
            $exp = explode('_', $str);
            $str = implode('', array_map(function ($a){return ucfirst($a);}, $exp));
            // ApplicationAttachments
        }

        return ucfirst($this->singularize($str));
    }

    public function check_migration_route($pattern, $name): void
    {
        if ($this->option('force')){
            return;
        }
        $found = [];
        $model_name = $this->format_to_get_model_name($this->get_final_table_name($pattern, $name));

        if (($this->option('m') || $this->option('all'))  && !in_array('m', $this->option_exceptions)){
            $model_path = app_path('Models') . "/{$model_name}.php";
            if (file_exists($model_path)){
                $found[] = ['Model', $model_name, $model_path];
            }
        }

        if (($this->option('c') || $this->option('all'))  && !in_array('c', $this->option_exceptions)){
            $controller_name = "{$model_name}Controller";
            $controller_path = app_path('Http/Controllers') . "/{$controller_name}.php";
            if (file_exists($controller_path)){
                $found[] = ['Controller', $controller_name, $controller_path];
            }
        }

        if (($this->option('b') || $this->option('all'))  && !in_array('b', $this->option_exceptions)){
            $parent_directory = base_path('resources/views/') . "{$this->str_to_lower($model_name)}";
            if (file_exists($parent_directory)){
                $views = ['create', 'edit', 'index', 'show'];
                foreach ($views as $view) {
                    $view_name = "{$view}.blade.php";
                    $view_path = "{$parent_directory}/{$view_name}";
                    if (file_exists($view_path)){
                        $found[] = ['View', $view_name, $view_path];
                    }
                }
            }
        }

        if (($this->option('r') || $this->option('all'))  && !in_array('r', $this->option_exceptions)){
            $all_routes = Route::getRoutes();
            $plural_model_name = $this->pluralize($this->str_to_lower($model_name));
            $routes = [
                "create-{$this->str_to_lower($model_name)}",
                "delete-{$plural_model_name}",
                "edit-{$this->str_to_lower($model_name)}",
                "show-{$this->str_to_lower($model_name)}",
                "store-{$this->str_to_lower($model_name)}",
                "view-{$plural_model_name}"
            ];

            $filtered_routes = array_filter($routes, function ($route) use ($all_routes) {
                return $all_routes->hasNamedRoute($route);
            });

            foreach ($filtered_routes as $filtered_route) {
                $found[] = ['Route', $filtered_route, null];
            }
        }

        if (($this->option('s') || $this->option('all'))  && !in_array('s', $this->option_exceptions)){
            $seeder_name = "{$model_name}Seeder";
            $seeder_path = database_path('seeders') . "/{$seeder_name}.php";
            if (file_exists($seeder_path)){
                $found[] = ['Seeder', $seeder_name, $seeder_path];
            }
        }

        if (($this->option('f') || $this->option('all'))  && !in_array('f', $this->option_exceptions)){
            $factory_name = "{$model_name}Factory";
            $factory_path = database_path('factories') . "/{$factory_name}.php";
            if (file_exists($factory_path)){
                $found[] = ['Factory', $factory_name, $factory_path];
            }
        }

        if (($this->option('p') || $this->option('all'))  && !in_array('p', $this->option_exceptions)){
            $test_name = "{$model_name}Test";
            $feature_test_path = base_path('tests') . "/Feature/{$test_name}.php";
            $unit_test_path = base_path('tests') . "/Unit/{$test_name}.php";
            $msg_path = null;
            if (file_exists($feature_test_path)){
                $msg_path = $feature_test_path;
            }
            if (file_exists($unit_test_path) && $msg_path === null){
                $msg_path = $unit_test_path;
            }

            if ($msg_path !== null){
                $found[] = ['Test', $test_name, $msg_path];
            }
        }

        if (!empty($found)){
            foreach ($found as $item) {
                $message = "{$item[0]} [{$item[1]}] already exists";
                if ($item[2] !== null){
                    $message .= " at the path {$item[2]}";
                }
                $this->comment($message);
            }

            $this->info("\nTo override the above existing files & routes, run the command with the --force flag");
            exit;
        }
    }

    public function perform_checks($route, $pattern, $name): void
    {
        if ($route === "migration_route"){
            $this->check_migration_route($pattern, $name);
        }
    }

    public function get_faker_string($fields): string
    {
        if (empty($fields)){
            return '//';
        }
        $str = "[" . PHP_EOL;
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
        $str .= "\t];";
        return $str;
    }

}
