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
        $str = "return view('{$view_directory_name}.show', compact('$data_variable'));";

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
        $str = "return view('{$view_directory_name}.edit', compact('$data_variable'));";

        $directory = "resources/views/{$view_directory_name}";
        $file = "{$directory}/edit.blade.php";

        $this->create_view($directory, $file);

        return $str;
    }

    public function getUpdateString($modelName, $fields): string
    {
        $data_variable = $this->str_to_lower($modelName);
        $str = "\${$data_variable} = {$modelName}::findOrFail(\$request->{$data_variable}_id);" . PHP_EOL . "\t\t";
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
        $str .= "$modelName::destroy(\$ids);" . PHP_EOL . "\t\t\t";
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

        $stub = str_replace('{{ lowerModelName }}', $this->str_to_lower($modelName), $stub);

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

    public function getRoutesString($controllerName, $modelName): string
    {
        $pluralizedModelName = $this->str_to_lower($this->pluralize($modelName));
        $modelName = $this->str_to_lower($modelName);
        $str = "Route::controller($controllerName::class)->middleware([])->group(function (){" . PHP_EOL . "\t";
        $str .= "Route::get('$pluralizedModelName', 'index')->name('view-$pluralizedModelName');" . PHP_EOL . "\t";
        $str .= "Route::get('$pluralizedModelName/{{$modelName}}', 'show')->name('show-$modelName');" . PHP_EOL . "\t";
        $str .= "Route::get('edit-$modelName/{{$modelName}}', 'edit')->name('edit-$modelName');" . PHP_EOL . "\t";
        $str .= "Route::get('create-$modelName', 'create')->name('create-$modelName');" . PHP_EOL . "\t";
        $str .= "Route::post('store-$modelName', 'store')->name('store-$modelName');" . PHP_EOL . "\t";
        $str .= "Route::post('update-$modelName', 'update')->name('update-$modelName');" . PHP_EOL . "\t";
        $str .= "Route::post('delete-$pluralizedModelName', 'destroy')->name('delete-$pluralizedModelName');" . PHP_EOL;
        $str .= "});";
        return $str;
    }

    public function create_routes($controllerName, $modelName): void
    {
        $file_path = "routes/web.php";
        $routesString = $this->getRoutesString($controllerName, $modelName);

        $new_file = file_get_contents(base_path($file_path));

        $controller_name_space = "use App\Http\Controllers\\$controllerName;";

        if (!str_contains($new_file, $controller_name_space)){
            $replace_string = "<?php" . PHP_EOL;
            $replace_string .= $controller_name_space;

            $new_file_contents = str_replace("<?php", $replace_string, $new_file);

            file_put_contents(base_path($file_path), $new_file_contents);
        }

        $file = fopen(base_path($file_path), 'a+');
        fwrite($file, PHP_EOL . $routesString);
        fclose($file);
    }

    public function createController($controllerName, $modelName, $fields, $controllerType = "base"): string
    {
        $controllerFile = app_path('Http/Controllers') . '/' . $controllerName . '.php';
        $stubName = $this->getControllerStubName($controllerType);
        $codegen_path = $this->codegen_path("stubs/controller.{$stubName}.stub");

        $stub = $this->controller_buffer($codegen_path, $modelName, $controllerName, $fields);

        file_put_contents($controllerFile, $stub);

        $this->formatFile($controllerFile);

        $this->create_routes($controllerName, $modelName);

        return $controllerFile;
    }
}
