<?php

namespace Tgozo\LaravelCodegen\Controllers;

use Tgozo\LaravelCodegen\Console\BaseTrait;

class Livewire
{
    use BaseTrait;

    const COMPONENT_NAMES = ['View', 'Show', 'Edit', 'Create', 'Store', 'Update', 'Delete'];

    const IS_COMPONENT_PREFIXED = ['view' => false, 'show' => false, 'edit' => true, 'create' => true, 'store' => true, 'update' => true, 'delete' => true];

    const COMPONENT_NEEDS_ID = ['view' => false, 'show' => true, 'edit' => true, 'create' => false, 'store' => false, 'update' => false, 'delete' => false];

    const COMPONENT_REQUEST_TYPE = ['view' => 'get', 'show' => 'get', 'edit' => 'get', 'create' => 'get', 'store' => 'post', 'update' => 'post', 'delete' => 'post'];

    const COMPONENT_NAMES_NAMESPACE = ['view' => 'plural', 'show' => 'singular', 'edit' => 'singular', 'create' => 'singular', 'store' => 'singular', 'update' => 'singular', 'delete' => 'plural'];

    protected array $pretendMessages = ['success' => [], 'failures' => []];

    public function __construct(protected readonly mixed $package, protected string $modelName, protected readonly array $fields)
    {
    }

    public function __invoke(): void
    {
        $this->createComponents();
    }

    public function createComponents(): void
    {
        // modelName => Dog
        $this->pretend();

        if (count($this->pretendMessages['failures']) !== 0 && !$this->package->option('force')){
            // list existing components
            foreach ($this->pretendMessages['failures'] as $failure){
                $this->package->error("Livewire Component: {$this->modelName}/{$failure} already exists");
            }
            exit;
        }

        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $this->createComponent($COMPONENT_NAME);
        }

        $this->replaceComponents(self::COMPONENT_NAMES);

//        $this->create_routes();
    }

    public function pretend(): void
    {
        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $exists = $this->checkIfComponentExists($COMPONENT_NAME);
            if ($exists){
                $this->pretendMessages['failures'][] = $COMPONENT_NAME;
            }else{
                $this->pretendMessages['success'][] = $COMPONENT_NAME;
            }
        }
    }

    public function checkIfComponentExists(string $componentName): bool
    {
        $component_path = app_path("Livewire") . "/{$this->modelName}/{$componentName}.php";
        return file_exists($component_path);
    }

    public function createComponent(string $componentName): void
    {

        shell_exec("php artisan livewire:make {$this->modelName}/{$componentName}");
        $this->package->info("Livewire Component: {$this->modelName}/{$componentName} created");
    }

    public function get_properties_string($fields): string
    {
        $str = "";

        foreach ($fields as $field) {
            if (!$field['nullable']){
                $str .= "#[Rule('required', message: '{$field['name']} is required')]\n\t";
                if (!in_array("Livewire\Attributes\Rule", $this->namespacesToAdd)){
                    $this->namespacesToAdd[] = "Livewire\Attributes\Rule";
                }
            }
            $str .= "public \${$field['name']};\n\n\t";
        }

        return trim($str);
    }

    public function generateCreateComponent()
    {
        $stub = $this->load_stub('livewire.create');

        $properties = $this->get_properties_string($this->fields);

        list($createParameters) = $this->get_update_or_store_string($this->fields, "create", "this");

        $additionalNameSpacesString = $this->getAdditionalNameSpacesString();

        $stub = str_replace('{{ modelName }}', $this->modelName, $stub);

        $stub = str_replace('{{ additionalNamespaces }}', $additionalNameSpacesString, $stub);

        $stub = str_replace('{{ properties }}', $properties, $stub);

        $stub = str_replace('{{ lowerSingularModelName }}', $this->str_to_lower($this->modelName), $stub);

        $stub = str_replace('{{ parameters }}', $createParameters, $stub);

        $stub = str_replace('{{ lowerModelName }}', $this->str_to_lower($this->modelName), $stub);

        $createComponentFile = app_path("Livewire/{$this->modelName}") . '/Create.php';

        file_put_contents($createComponentFile, $stub);
    }

    public function replaceComponents($componentNames): void
    {
        foreach ($componentNames as $componentName) {
            $functionName = "generate{$componentName}Component";
            if (!method_exists($this, $functionName))
                continue;
            $this->{$functionName}();
        }
    }

    public function getRoutesString(): string
    {
        $str = "";

        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $plural = $this->isPlural($COMPONENT_NAME);
            if ($plural){
                $route_model_string = $this->str_to_lower($this->pluralize($this->modelName));
            }else{
                $route_model_string = $this->str_to_lower($this->singularize($this->modelName));
            }
            $as = $this->return_as($COMPONENT_NAME);
            $request_type = self::COMPONENT_REQUEST_TYPE[$this->str_to_lower($COMPONENT_NAME)];
            $is_prefixed = self::IS_COMPONENT_PREFIXED[$this->str_to_lower($COMPONENT_NAME)];
            $needs_id = self::COMPONENT_NEEDS_ID[$this->str_to_lower($COMPONENT_NAME)];

            if ($is_prefixed){
                $url = "{$this->str_to_lower($COMPONENT_NAME)}-$route_model_string";
            }else{
                $url = $route_model_string;
            }

            if ($needs_id){
                $url .= "/{id}";
            }

            $str .= "Route::$request_type('$url', $as::class)->name('{$this->str_to_lower($COMPONENT_NAME)}-$route_model_string');" . PHP_EOL;
        }

        return $str;
    }

    public function create_routes(): void
    {
        $file_path = "routes/web.php";
        $routesString = $this->getRoutesString();

        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $as = $this->return_as($COMPONENT_NAME);
            $name_space = "use App\Livewire\\$this->modelName\\$COMPONENT_NAME as {$as};";

            $new_file = file_get_contents(base_path($file_path));

            if (!str_contains($new_file, $name_space)){
                $replace_string = "<?php" . PHP_EOL;
                $replace_string .= $name_space;

                $new_file_contents = str_replace("<?php", $replace_string, $new_file);

                file_put_contents(base_path($file_path), $new_file_contents);
            }
        }

        $file = fopen(base_path($file_path), 'a+');
        fwrite($file, PHP_EOL . $routesString);
        fclose($file);
    }

    public function return_as(string $COMPONENT_NAME): string
    {
        $plural = $this->isPlural($COMPONENT_NAME);
        if ($plural) {
            $as = "{$COMPONENT_NAME}{$this->pluralize($this->modelName)}";
        } else {
            $as = "{$COMPONENT_NAME}{$this->singularize($this->modelName)}";
        }

        return $as;
    }

    public function isPlural(string $COMPONENT_NAME): bool
    {
        return self::COMPONENT_NAMES_NAMESPACE[$this->str_to_lower($COMPONENT_NAME)] === 'plural';
    }
}
