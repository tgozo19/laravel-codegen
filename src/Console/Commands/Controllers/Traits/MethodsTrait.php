<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Controllers\Traits;

use Illuminate\Foundation\Inspiring;
use function PHPUnit\Framework\directoryExists;

trait MethodsTrait
{
    use AttributesTrait;
    public function getControllerStubName($name)
    {
        return $this->controller_stub_names[$name];
    }

    public function controller_buffer(string $codegen_path, $modelName, $controllerName): string|array|bool
    {
        $stub = file_get_contents($codegen_path);

        $name_space = "App\Http\Controllers";
        $root_name_space = "App\\";

        $fetchString = $this->getFetchString($modelName);

        $stub = str_replace('{{ namespace }}', $name_space, $stub);

        $stub = str_replace('{{ rootNamespace }}', $root_name_space, $stub);

        $stub = str_replace('{{ modelName }}', $modelName, $stub);

        $stub = str_replace('{{ controllerName }}', $controllerName, $stub);

        $stub = str_replace('{{ fetchCode }}', $fetchString, $stub);

        $stub = str_replace('{{ createCode }}', "", $stub);

        $stub = str_replace('{{ storeCode }}', "", $stub);

        $stub = str_replace('{{ showCode }}', "", $stub);

        $stub = str_replace('{{ editCode }}', "", $stub);

        $stub = str_replace('{{ updateCode }}', "", $stub);

        return str_replace('{{ destroyCode }}', "", $stub);
    }

    public function getFetchString($modelName): string
    {
        $pluralizedModelName = $this->pluralize($modelName);
        $data_variable = $this->str_to_lower($pluralizedModelName);
        $view_directory_name = $this->str_to_lower($modelName);
        $str = "\${$data_variable} = {$modelName}::query()->paginate();" . PHP_EOL . "\t\t";
        $str .= "return view('{$view_directory_name}.index', compact('$data_variable'));";

        $directory = "resources/views/{$view_directory_name}";
        $file = "{$directory}/index.blade.php";

        if (!file_exists($directory)){
            mkdir(base_path($directory));
        }
        $quote = Inspiring::quotes()->random();

        $codegen_path = $this->codegen_path("stubs/blank_view.stub");

        $blank_view = file_get_contents($codegen_path);

        $blank_view = str_replace('quote', $quote, $blank_view);

        file_put_contents($file, $blank_view);

        return $str;
    }

    public function createController($controllerName, $modelName, $fields, $controllerType = "base"): void
    {
        $controllerFile = app_path('Http/Controllers') . '/' . $controllerName . '.php';
        $stubName = $this->getControllerStubName($controllerType);
        $codegen_path = $this->codegen_path("stubs/controller.{$stubName}.stub");

        $stub = $this->controller_buffer($codegen_path, $modelName, $controllerName);

        file_put_contents($controllerFile, $stub);

        $this->formatFile($controllerFile);

        $this->info("Created Controller");
    }
}
