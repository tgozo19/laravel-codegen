<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Controllers\Traits;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait MethodsTrait
{
    use AttributesTrait;

    public function create_view(string $directory, string $file): void
    {
        if (!file_exists($directory)) {
            mkdir(base_path($directory));
        }
        $quote = Inspiring::quotes()->random();

        $codegen_path = $this->codegen_path("stubs/blank_view.stub");

        $blank_view = file_get_contents($codegen_path);

        $blank_view = str_replace('quote', $quote, $blank_view);

        file_put_contents($file, $blank_view);
    }

    public function getControllerStubName($name)
    {
        return $this->controller_stub_names[$name];
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

        $this->create_view($directory, $file);

        return $str;
    }

    public function getCreateString($modelName): string
    {
        $view_directory_name = $this->str_to_lower($modelName);

        $directory = "resources/views/{$view_directory_name}";
        $file = "{$directory}/create.blade.php";

        $this->create_view($directory, $file);

        return "return view('{$view_directory_name}.create');";
    }

    public function get_update_or_store_string($fields, $type): array
    {
        $str = "";
        $fields_have_password = false;
        foreach ($fields as $index => $field) {
            $field_name = $field['name'];

            if ($field_name === "password"){
                if ($type === "update") continue;
                if ($fields_have_password !== true){
                    $fields_have_password = true;
                }
            }

            $field_value = ($field_name === "password") ? "Hash::make(\$request->{$field_name})" : "\$request->{$field_name}";

            $tabs = ($index === count($fields) - 1) ? "\t\t\t" : "\t\t\t\t";

            $str .= "'{$field_name}' => {$field_value}," . PHP_EOL . $tabs;
        }

        return [$str, $fields_have_password];
    }

    public function getStoreString($modelName, $fields): array
    {
        $str = "try {" . PHP_EOL . "\t\t\t";
        $str .= "DB::beginTransaction();" . PHP_EOL . "\t\t\t";
        $str .= "{$modelName}::create([" . PHP_EOL . "\t\t\t\t";

        [$string, $has_hash_namespace] = $this->get_update_or_store_string($fields, "store");
        $str .= $string;

        $str .= "]);" . PHP_EOL . "\t\t\t";
        $str .= "DB::commit();" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('success', '{$modelName} Created Successfully');" . PHP_EOL . "\t\t";
        $str .= "}catch (Exception \$e){" . PHP_EOL . "\t\t\t";
        $str .= "DB::rollBack();" . PHP_EOL . "\t\t\t";
        $str .= "info(\$e->getMessage());" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('error', 'Failed to create {$modelName}. Please Try Again');" . PHP_EOL . "\t\t";
        $str .= "}";

        return [$str, $has_hash_namespace];
    }

    public function getShowString($modelName): string
    {
        $singularizedModelName = $this->singularize($modelName);
        $data_variable = $this->str_to_lower($singularizedModelName);
        $view_directory_name = $this->str_to_lower($modelName);
        $str = "\${$data_variable} = {$modelName}::findOrFail(\$id);" . PHP_EOL . "\t\t";
        $str .= "return view('{$view_directory_name}.show', compact('$data_variable'));";

        $directory = "resources/views/{$view_directory_name}";
        $file = "{$directory}/show.blade.php";

        $this->create_view($directory, $file);

        return $str;
    }

    public function getEditString($modelName): string
    {
        $singularizedModelName = $this->singularize($modelName);
        $data_variable = $this->str_to_lower($singularizedModelName);
        $view_directory_name = $this->str_to_lower($modelName);
        $str = "\${$data_variable} = {$modelName}::findOrFail(\$id);" . PHP_EOL . "\t\t";
        $str .= "return view('{$view_directory_name}.edit', compact('$data_variable'));";

        $directory = "resources/views/{$view_directory_name}";
        $file = "{$directory}/edit.blade.php";

        $this->create_view($directory, $file);

        return $str;
    }

    public function getUpdateString($modelName, $fields): string
    {
        $data_variable = $this->str_to_lower($modelName);
        $str = "\${$data_variable} = {$modelName}::findOrFail(\$id);";
        $str .= "try {" . PHP_EOL . "\t\t\t";
        $str .= "DB::beginTransaction();" . PHP_EOL . "\t\t\t";
        $str .= "\${$data_variable}->update([" . PHP_EOL . "\t\t\t\t";

        [$string, $has_hash_namespace] = $this->get_update_or_store_string($fields, "update");
        $str .= $string;

        $str .= "]);" . PHP_EOL . "\t\t\t";
        $str .= "DB::commit();" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('success', '{$modelName} Updated Successfully');" . PHP_EOL . "\t\t";
        $str .= "}catch (Exception \$e){" . PHP_EOL . "\t\t\t";
        $str .= "DB::rollBack();" . PHP_EOL . "\t\t\t";
        $str .= "info(\$e->getMessage());" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('error', 'Failed to update {$modelName}. Please Try Again');" . PHP_EOL . "\t\t";
        $str .= "}";

        return $str;
    }

    public function getDestroyString($modelName): string
    {
        $str = "try {" . PHP_EOL . "\t\t\t";
        $str .= "$modelName::destroy(\$id);" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('success', '{$modelName} Deleted Successfully');" . PHP_EOL . "\t\t";
        $str .= "}catch (Exception \$e){" . PHP_EOL . "\t\t\t";
        $str .= "info(\$e->getMessage());" . PHP_EOL . "\t\t\t";
        $str .= "return back()->with('error', 'Failed to delete {$modelName}. Please Try Again');" . PHP_EOL . "\t\t";
        $str .= "}";

        return $str;
    }

    public function controller_buffer(string $codegen_path, $modelName, $controllerName, $fields): string|array|bool
    {
        $stub = file_get_contents($codegen_path);

        $name_space = "App\Http\Controllers";
        $root_name_space = "App\\";
        $pluralizedModelName = $this->pluralize($modelName);

        $fetchString = $this->getFetchString($modelName);

        $createString = $this->getCreateString($modelName);

        [$storeString, $has_hash_name_space] = $this->getStoreString($modelName, $fields);

        if ($has_hash_name_space === true){
            $hash_name_space = "use Illuminate\Support\Facades\Hash;";

            $stub = str_replace('{{ hasHash }}', $hash_name_space, $stub);
        }else{
            $stub = str_replace('{{ hasHash }}', "", $stub);
        }

        $showString = $this->getShowString($modelName);

        $editString = $this->getEditString($modelName);

        $updateString = $this->getUpdateString($modelName, $fields);

        $destroyString = $this->getDestroyString($modelName);

        $stub = str_replace('{{ namespace }}', $name_space, $stub);

        $stub = str_replace('{{ rootNamespace }}', $root_name_space, $stub);

        $stub = str_replace('{{ modelName }}', $modelName, $stub);

        $stub = str_replace('{{ pluralizedModelName }}', $pluralizedModelName, $stub);

        $stub = str_replace('{{ controllerName }}', $controllerName, $stub);

        $stub = str_replace('{{ fetchCode }}', $fetchString, $stub);

        $stub = str_replace('{{ createCode }}', $createString, $stub);

        $stub = str_replace('{{ storeCode }}', $storeString, $stub);

        $stub = str_replace('{{ showCode }}', $showString, $stub);

        $stub = str_replace('{{ editCode }}', $editString, $stub);

        $stub = str_replace('{{ updateCode }}', $updateString, $stub);

        return str_replace('{{ destroyCode }}', $destroyString, $stub);
    }

    public function createController($controllerName, $modelName, $fields, $controllerType = "base"): void
    {
        $controllerFile = app_path('Http/Controllers') . '/' . $controllerName . '.php';
        $stubName = $this->getControllerStubName($controllerType);
        $codegen_path = $this->codegen_path("stubs/controller.{$stubName}.stub");

        $stub = $this->controller_buffer($codegen_path, $modelName, $controllerName, $fields);

        file_put_contents($controllerFile, $stub);

        $this->formatFile($controllerFile);

        $this->info("Created Controller");
    }
}
