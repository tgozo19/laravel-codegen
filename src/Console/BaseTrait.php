<?php

namespace Tgozo\LaravelCodegen\Console;

use Doctrine\Inflector\Inflector;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait BaseTrait
{
    private array $passedOptions = [];
    protected array $namespacesToAdd = [];

    protected array $relationships = [];

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

        $custom_stub = base_path("resources/stubs/vendor/laravelcodegen/{$name}.stub");
        if (file_exists($custom_stub)) {
            return file_get_contents($custom_stub);
        }

        $dir_name = dirname(__DIR__, 1) . "/stubs/{$name}.stub";
        return file_get_contents($dir_name);
    }

    public function snakeToCamelPlural($string): string
    {
        return Str::plural(Str::camel($string));
    }

    public function snakeToCamelSingular($string): string
    {
        return Str::singular(Str::camel($string));
    }

    public function formatFile($file): void
    {
        $contents = file_get_contents($file);
        $contents = preg_replace("/\n\s*\n/", "\n\n", $contents);
        file_put_contents($file, $contents);
    }

    public function singularize($str): string
    {
        return Str::singular($str);
    }

    public function pluralize($str): string
    {
        return Str::plural($str);
    }

    public function str_to_lower($str): string
    {
        return Str::lower($str);
    }

    public function str_to_upper($str): string
    {
        return Str::upper($str);
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
        $str = Str::lower($str);
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

    public function check_migration_route($pattern, $name): bool
    {
        if ($this->option('force')){
            return false;
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
            return true;
        }

        return false;
    }

    public function perform_checks($route, $pattern, $name): bool
    {
        if ($route === "migration_route"){
            return $this->check_migration_route($pattern, $name);
        }

        return false;
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

    public function getAdditionalNameSpacesString(): string
    {
        $str = "";

        foreach ($this->namespacesToAdd as $index => $nameSpace) {
            $str .= "use {$nameSpace};";
            if ($index !== count($this->namespacesToAdd) - 1){
                $str .= "\n";
            }
        }

        return $str;
    }

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

    public function get_update_or_store_string($fields, $type, $field_source = "request"): array
    {
        $str = "";
        $fields_have_password = false;
        foreach ($fields as $index => $field) {
            $field_name = $field['name'];

            if ($field_name === "password"){
                if ($type === "update") continue;
                if ($fields_have_password !== true){
                    if (!in_array("Illuminate\Support\Facades\Hash", $this->namespacesToAdd)){
                        $this->namespacesToAdd[] = "Illuminate\Support\Facades\Hash";
                    }
                    $fields_have_password = true;
                }
            }

            $field_value = $this->getFieldValue($field_source, $field_name);

            $tabs = ($index === count($fields) - 1) ? "\t\t\t" : "\t\t\t\t";

            $str .= "'{$field_name}' => {$field_value}," . PHP_EOL . $tabs;
        }

        return [trim($str), $fields_have_password];
    }

    public function getFieldValue($field_source, $field_name): string
    {
        if ($field_name === 'password'){
            return "Hash::make(\${$field_source}->{$field_name})";
        }

        if ($field_name === 'user_id'){
            return "auth()->user()->id";
        }

        return "\${$field_source}->{$field_name}";
    }

    public function getFetchString($modelName, $view = 'index', $prefix = ''): string
    {
        $directoryPrefix = '';
        if ($prefix !== ''){
            $directoryPrefix = $prefix . '/';
            $prefix = $prefix . '.';
        }

        $data_variable = $this->getDataVariable($modelName);

        $view_directory_name = $this->getViewDirectoryName($modelName);
        $str = "\${$data_variable} = {$modelName}::query()->paginate();" . PHP_EOL . "\t\t";
        $str .= "return view('{$prefix}{$view_directory_name}.{$view}', compact('$data_variable'));";

        $directory = "resources/views/{$directoryPrefix}{$view_directory_name}";
        $file = "{$directory}/{$view}.blade.php";

        $this->create_view($directory, $file);

        return $str;
    }

    public function getDataVariable($modelName, $separator = '_', $plural = true): string
    {
        if ($plural){
            $modelName = $this->pluralize($modelName);
        }
        $modelNameCharacters = str_split($modelName);
        $data_variable = '';
        foreach ($modelNameCharacters as $index => $modelNameCharacter) {
            if (ctype_upper($modelNameCharacter) && $index !== 0){
                $modelNameCharacter = $separator . $modelNameCharacter;
            }
            $data_variable .= $modelNameCharacter;
        }

        return $this->str_to_lower($data_variable);
    }

    public function getModelTitle($modelName, $plural = false, $separator = ' '): string
    {
        if ($plural){
            $modelName = $this->pluralize($modelName);
        }
        $modelNameCharacters = str_split($modelName);
        $title = '';
        foreach ($modelNameCharacters as $index => $modelNameCharacter) {
            if (ctype_upper($modelNameCharacter) && $index !== 0){
                $modelNameCharacter = $separator . $modelNameCharacter;
            }
            $title .= $modelNameCharacter;
        }

        return $title;
    }

    public function getViewDirectoryName($modelName): string
    {
        $modelNameCharacters = str_split($modelName);
        $data_variable = '';
        foreach ($modelNameCharacters as $index => $modelNameCharacter) {
            if (ctype_upper($modelNameCharacter) && $index !== 0){
                $modelNameCharacter = '-' . $modelNameCharacter;
            }
            $data_variable .= $modelNameCharacter;
        }

        return $this->str_to_lower($data_variable);
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

    public function registerNamespace($namespace): void
    {
        if (!in_array($namespace, $this->namespacesToAdd)){
            $this->namespacesToAdd[] = $namespace;
        }
    }

    public function addNamespaces($file, $use_file = true): array|bool|int|string
    {
        if ($use_file){
            $file_contents = file_get_contents($file);
        }else{
            $file_contents = $file;
        }
        $class_pos = strpos($file_contents, "class");

        $new_code = trim($this->getAdditionalNameSpacesString());

        $new_file_contents = substr_replace($file_contents, "\t$new_code\n\n", $class_pos, 0);

        if (!$use_file){
            return $new_file_contents;
        }
        return file_put_contents($file, $new_file_contents);
    }

    public function promptText(string $label, string $placeholder = '', string $default = '', ?callable $validate = null): string
    {
        if (function_exists('Laravel\Prompts\text')) {
            return \Laravel\Prompts\text(
                label: $label,
                placeholder: $placeholder,
                default: $default,
                validate: $validate
            );
        }

        return $this->ask($label, $default) ?? '';
    }

    public function promptSelect(string $label, array $options, mixed $default = null): string
    {
        if (function_exists('Laravel\Prompts\select')) {
            return \Laravel\Prompts\select(
                label: $label,
                options: $options,
                default: $default
            );
        }

        return $this->choice($label, $options, $default);
    }

    public function promptSuggest(string $label, array $options, string $placeholder = '', string $default = ''): string
    {
        if (function_exists('Laravel\Prompts\suggest')) {
            return \Laravel\Prompts\suggest(
                label: $label,
                options: $options,
                placeholder: $placeholder,
                default: $default
            );
        }

        return $this->choice($label, $options, $default);
    }

    public function promptConfirm(string $label, bool $default = true): bool
    {
        if (function_exists('Laravel\Prompts\confirm')) {
            return \Laravel\Prompts\confirm(
                label: $label,
                default: $default
            );
        }

        return $this->confirm($label, $default);
    }

    public function save_file(string $path, string $content, bool $isDryRun = false): void
    {
        if ($isDryRun) {
            $this->comment("[DRY RUN] Would create/write to file: {$path}");
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("Created file: {$path}");
    }

    public function getDomainPath(string $subpath, ?string $domain = null): string
    {
        if (!empty($domain)) {
            $domainName = Str::studly($domain);
            return app_path("Domain/{$domainName}/{$subpath}");
        }

        return app_path($subpath);
    }

    public function inferValidationRules(array $fields): string
    {
        $rulesLines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = strtolower($field['type'] ?? 'string');

            if (!$name || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $rule = ['required'];
            if (str_contains($type, 'nullable')) {
                $rule = ['nullable'];
            }

            if (str_contains($type, 'int')) {
                $rule[] = 'integer';
            } elseif (str_contains($type, 'bool')) {
                $rule[] = 'boolean';
            } elseif (str_contains($type, 'date') || str_contains($type, 'time')) {
                $rule[] = 'date';
            } elseif (str_contains($type, 'json')) {
                $rule[] = 'array';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:255';
            }

            if ($name === 'email') {
                $rule[] = 'email';
            }

            $rulesLines[] = "            '{$name}' => '" . implode('|', $rule) . "',";
        }

        return implode("\n", $rulesLines);
    }

    public function generateFormRequests(string $modelName, array $fields, ?string $domain = null, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $rules = $this->inferValidationRules($fields);

        $namespace = !empty($domain)
            ? "App\\Domain\\" . Str::studly($domain) . "\\Requests"
            : "App\\Http\\Requests";

        $subDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Requests" : "Http/Requests";

        $stub = $this->load_stub('request');

        foreach (['Store', 'Update'] as $action) {
            $className = "{$action}{$modelStudly}Request";
            $content = str_replace(
                ['{{ namespace }}', '{{ class }}', '{{ rules }}'],
                [$namespace, $className, $rules],
                $stub
            );

            $filePath = app_path("{$subDir}/{$className}.php");
            $this->save_file($filePath, $content, $isDryRun);
        }
    }

    public function generateEnum(string $enumName, array $cases, ?string $domain = null, bool $isDryRun = false): void
    {
        $enumStudly = Str::studly($enumName);
        $namespace = !empty($domain)
            ? "App\\Domain\\" . Str::studly($domain) . "\\Enums"
            : "App\\Enums";

        $subDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Enums" : "Enums";

        $casesLines = [];
        $labelsLines = [];
        foreach ($cases as $case) {
            $cleanCase = trim($case);
            if (empty($cleanCase)) continue;
            $caseName = Str::studly($cleanCase);
            $casesLines[] = "    case {$caseName} = '{$cleanCase}';";
            $labelsLines[] = "            self::{$caseName} => '" . Str::headline($cleanCase) . "',";
        }

        $stub = $this->load_stub('enum');
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ cases }}', '{{ labels }}'],
            [$namespace, "{$enumStudly}Enum", implode("\n", $casesLines), implode("\n", $labelsLines)],
            $stub
        );

        $filePath = app_path("{$subDir}/{$enumStudly}Enum.php");
        $this->save_file($filePath, $content, $isDryRun);
    }

    public function generateInertiaComponents(string $modelName, array $fields, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $modelCamel = Str::camel($modelName);
        $modelKebab = Str::kebab(Str::plural($modelName));

        $indexStub = $this->load_stub('inertia.index.vue');
        $indexContent = str_replace(
            ['{{ modelPlural }}', '{{ modelSingular }}', '{{ modelRoute }}'],
            [Str::plural($modelStudly), $modelStudly, $modelKebab],
            $indexStub
        );

        $indexPath = resource_path("js/Pages/{$modelStudly}/Index.vue");
        $this->save_file($indexPath, $indexContent, $isDryRun);

        $createStub = $this->load_stub('inertia.create.vue');
        $formFields = [];
        $formElements = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;
            $formFields[] = "    {$name}: '',";
            $label = Str::headline($name);
            $formElements[] = "            <div>\n                <label class=\"block text-sm font-medium text-gray-700\">{$label}</label>\n                <input v-model=\"form.{$name}\" type=\"text\" class=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm\" />\n            </div>";
        }

        $createContent = str_replace(
            ['{{ modelSingular }}', '{{ modelRoute }}', '{{ fields }}', '{{ formElements }}'],
            [$modelStudly, $modelKebab, implode("\n", $formFields), implode("\n", $formElements)],
            $createStub
        );

        $createPath = resource_path("js/Pages/{$modelStudly}/Create.vue");
        $this->save_file($createPath, $createContent, $isDryRun);
    }

    public function generateTypeScriptDefinition(string $modelName, array $fields, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $props = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (!$name) continue;

            $type = strtolower($field['type'] ?? 'string');
            $tsType = 'string';

            if (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
                $tsType = 'number';
            } elseif (str_contains($type, 'bool')) {
                $tsType = 'boolean';
            } elseif (str_contains($type, 'json') || str_contains($type, 'array')) {
                $tsType = 'Record<string, any>';
            }

            $nullable = !empty($field['nullable']);
            $propName = $nullable ? "{$name}?" : $name;
            $propType = $nullable ? "{$tsType} | null" : $tsType;

            $props[] = "    {$propName}: {$propType};";
        }

        $stub = $this->load_stub('typescript');
        $content = str_replace(
            ['{{ class }}', '{{ properties }}'],
            [$modelStudly, implode("\n", $props)],
            $stub
        );

        $path = resource_path("js/types/{$modelStudly}.d.ts");
        $this->save_file($path, $content, $isDryRun);
    }

    public function generateDomainEvents(string $modelName, ?string $domain = null, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $modelVariable = Str::camel($modelName);

        $eventNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Events" : "App\\Events";
        $eventSubDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Events" : "Events";
        $modelNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Models\\{$modelStudly}" : "App\\Models\\{$modelStudly}";

        $eventClassName = "{$modelStudly}Created";
        $eventStub = $this->load_stub('event');
        $eventContent = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ namespacedModel }}', '{{ model }}', '{{ modelVariable }}'],
            [$eventNamespace, $eventClassName, $modelNamespace, $modelStudly, $modelVariable],
            $eventStub
        );

        $eventPath = app_path("{$eventSubDir}/{$eventClassName}.php");
        $this->save_file($eventPath, $eventContent, $isDryRun);

        $listenerNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Listeners" : "App\\Listeners";
        $listenerSubDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Listeners" : "Listeners";
        $namespacedEvent = "{$eventNamespace}\\{$eventClassName}";

        $listenerClassName = "Handle{$eventClassName}";
        $listenerStub = $this->load_stub('listener');
        $listenerContent = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ namespacedEvent }}', '{{ event }}'],
            [$listenerNamespace, $listenerClassName, $namespacedEvent, $eventClassName],
            $listenerStub
        );

        $listenerPath = app_path("{$listenerSubDir}/{$listenerClassName}.php");
        $this->save_file($listenerPath, $listenerContent, $isDryRun);
    }

    public function generateInertiaReactComponents(string $modelName, array $fields, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $modelKebab = Str::kebab(Str::plural($modelName));

        $indexStub = $this->load_stub('inertia.index.react');
        $indexContent = str_replace(
            ['{{ modelSingular }}', '{{ modelRoute }}'],
            [$modelStudly, $modelKebab],
            $indexStub
        );

        $indexPath = resource_path("js/Pages/{$modelStudly}/Index.tsx");
        $this->save_file($indexPath, $indexContent, $isDryRun);

        $createStub = $this->load_stub('inertia.create.react');
        $formFields = [];
        $formElements = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;
            $formFields[] = "        {$name}: '',";
            $label = Str::headline($name);
            $formElements[] = "                <div>\n                    <label className=\"block text-sm font-medium text-gray-700\">{$label}</label>\n                    <input value={data.{$name}} onChange={e => setData('{$name}', e.target.value)} type=\"text\" className=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm\" />\n                </div>";
        }

        $createContent = str_replace(
            ['{{ modelSingular }}', '{{ modelRoute }}', '{{ fields }}', '{{ formElements }}'],
            [$modelStudly, $modelKebab, implode("\n", $formFields), implode("\n", $formElements)],
            $createStub
        );

        $createPath = resource_path("js/Pages/{$modelStudly}/Create.tsx");
        $this->save_file($createPath, $createContent, $isDryRun);
    }

    public function generateRepositoryPattern(string $modelName, ?string $domain = null, bool $isDryRun = false): void
    {
        $modelStudly = Str::studly($modelName);
        $interfaceNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Repositories\\Contracts" : "App\\Repositories\\Contracts";
        $interfaceSubDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Repositories/Contracts" : "Repositories/Contracts";
        $modelNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Models\\{$modelStudly}" : "App\\Models\\{$modelStudly}";

        $interfaceClassName = "{$modelStudly}RepositoryInterface";
        $interfaceStub = $this->load_stub('repository.interface');
        $interfaceContent = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ namespacedModel }}', '{{ model }}'],
            [$interfaceNamespace, $interfaceClassName, $modelNamespace, $modelStudly],
            $interfaceStub
        );

        $interfacePath = app_path("{$interfaceSubDir}/{$interfaceClassName}.php");
        $this->save_file($interfacePath, $interfaceContent, $isDryRun);

        $eloquentNamespace = !empty($domain) ? "App\\Domain\\" . Str::studly($domain) . "\\Repositories\\Eloquent" : "App\\Repositories\\Eloquent";
        $eloquentSubDir = !empty($domain) ? "Domain/" . Str::studly($domain) . "/Repositories/Eloquent" : "Repositories/Eloquent";
        $namespacedInterface = "{$interfaceNamespace}\\{$interfaceClassName}";

        $eloquentClassName = "{$modelStudly}Repository";
        $eloquentStub = $this->load_stub('repository.eloquent');
        $eloquentContent = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ namespacedInterface }}', '{{ namespacedModel }}', '{{ interfaceName }}', '{{ model }}'],
            [$eloquentNamespace, $eloquentClassName, $namespacedInterface, $modelNamespace, $interfaceClassName, $modelStudly],
            $eloquentStub
        );

        $eloquentPath = app_path("{$eloquentSubDir}/{$eloquentClassName}.php");
        $this->save_file($eloquentPath, $eloquentContent, $isDryRun);
    }
}

