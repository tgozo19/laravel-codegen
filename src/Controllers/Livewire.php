<?php

namespace Tgozo\LaravelCodegen\Controllers;

use Exception;
use Tgozo\LaravelCodegen\Console\BaseTrait;

class Livewire
{
    use BaseTrait;

    const COMPONENT_NAMES = ['View', 'Show', 'Edit', 'Create'];

    const IS_COMPONENT_PREFIXED = ['view' => false, 'show' => false, 'edit' => true, 'create' => true];

    const COMPONENT_NEEDS_ID = ['view' => false, 'show' => true, 'edit' => true, 'create' => false];

    const COMPONENT_REQUEST_TYPE = ['view' => 'get', 'show' => 'get', 'edit' => 'get', 'create' => 'get'];

    const COMPONENT_NAMES_NAMESPACE = ['view' => 'plural', 'show' => 'singular', 'edit' => 'singular', 'create' => 'singular'];

    protected array $pretendMessages = ['success' => [], 'failures' => []];

    /**
     * @throws Exception
     */
    public function __construct(protected readonly mixed $package, protected readonly string $modelName, protected readonly array $fields)
    {
        self::verifyInstallation(true);
    }

    /**
     * @throws Exception
     */
    public static function verifyInstallation($throwException = false): bool
    {
        $exists = file_exists(base_path('vendor/livewire/livewire'));

        if ($exists){
            return true;
        }

        if (!$throwException){
            return false;
        }

        throw new Exception("Livewire is not installed. Install Livewire first or remove the --l flag from the command");
    }

    public function __invoke(): void
    {
        $this->createComponents();
    }

    /**
     * @throws Exception
     */
    public function createComponents(): void
    {
        $this->pretend();

        if (count($this->pretendMessages['failures']) !== 0 && !$this->package->option('force')){
            foreach ($this->pretendMessages['failures'] as $failure){
                $this->package->error("Livewire Component: {$this->modelName}/{$failure} already exists");
            }
            exit;
        }

        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $this->createComponent($COMPONENT_NAME);
        }

        $this->replaceComponents(self::COMPONENT_NAMES);

        (new Routes($this->package, $this->modelName, $this->fields))->create_routes();
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

    public function get_properties_string($withAttributes = true): string
    {
        $str = "";

        foreach ($this->fields as $field) {
            if (!$field['nullable']){
                if ($withAttributes){
                    $str .= "#[Rule('required', message: '{$field['name']} is required')]\n\t";
                }
                if (!in_array("Livewire\Attributes\Rule", $this->namespacesToAdd)){
                    $this->namespacesToAdd[] = "Livewire\Attributes\Rule";
                }
            }
            $str .= "public \${$field['name']};\n\n\t";
        }

        return trim($str);
    }

    public function extractProperties(): array
    {
        $properties = [];
        foreach ($this->fields as $field) {
            $properties[] = $field['name'];
        }

        return $properties;
    }

    public function assignmentString(bool $reversed, string $property): string
    {
        if ($reversed){
            return "\$this->resource->{$property} = \$this->{$property};";
        }

        return "\$this->{$property} = \$this->resource->{$property};";
    }

    public function assignmentLogic(bool $reversed): string
    {
        $str = "";
        foreach ($this->extractProperties() as $property) {
            $str .= $this->assignmentString($reversed, $property) . PHP_EOL . "\t\t";
        }

        return trim($str);
    }

    public function generateCreateComponent(): void
    {
        $stub = $this->load_stub('livewire.create');

        list($createParameters) = $this->get_update_or_store_string($this->fields, "create", "this");

        $additionalNameSpacesString = $this->getAdditionalNameSpacesString();

        $stub = str_replace('{{ modelName }}', $this->modelName, $stub);

        $stub = str_replace('{{ additionalNamespaces }}', $additionalNameSpacesString, $stub);

        $properties = $this->get_properties_string();
        $stub = str_replace('{{ properties }}', $properties, $stub);

        $stub = str_replace('{{ methodModelName }}', $this->singularize($this->getDataVariable($this->modelName)), $stub);

        $stub = str_replace('{{ parameters }}', $createParameters, $stub);

        $stub = str_replace('{{ singularTitle }}', $this->getModelTitle($this->modelName), $stub);

        $stub = str_replace('{{ directoryName }}', $this->getViewDirectoryName($this->modelName), $stub);

        $stub = str_replace('{{ title }}', $this->getModelTitle($this->modelName, true), $stub);

        $createComponentFile = app_path("Livewire/{$this->modelName}") . '/Create.php';

        file_put_contents($createComponentFile, $stub);
    }

    public function generateViewComponent(): void
    {
        $stub = $this->load_stub('livewire.view');

        $fetchString = $this->getFetchString($this->modelName, 'view', 'livewire');

        $stub = str_replace('{{ modelName }}', $this->modelName, $stub);

        $stub = str_replace('{{ title }}', $this->getModelTitle($this->modelName, true), $stub);

        $stub = str_replace('{{ fetchString }}', $fetchString, $stub);

        $viewComponentFile = app_path("Livewire/{$this->modelName}") . '/View.php';

        file_put_contents($viewComponentFile, $stub);
    }

    public function generateShowComponent(): void
    {
        $stub = $this->load_stub('livewire.show');

        $stub = str_replace('{{ modelName }}', $this->modelName, $stub);

        $dataVariable = $this->getDataVariable($this->modelName, '_', false);
        $stub = str_replace('{{ dataVariable }}', $dataVariable, $stub);

        $stub = str_replace('{{ title }}', $this->getModelTitle($this->modelName), $stub);

        $routeVariable = $this->getDataVariable($this->modelName, '-', false);
        $stub = str_replace('{{ viewRoute }}', $this->pluralize($routeVariable), $stub);

        $stub = str_replace('{{ directoryName }}', $this->getViewDirectoryName($this->modelName), $stub);

        $showComponentFile = app_path("Livewire/{$this->modelName}") . '/Show.php';

        file_put_contents($showComponentFile, $stub);
    }

    public function generateEditComponent(): void
    {
        $stub = $this->load_stub('livewire.edit');

        $stub = str_replace('{{ modelName }}', $this->modelName, $stub);

        $properties = $this->get_properties_string(false);
        $stub = str_replace('{{ properties }}', $properties, $stub);

        $mountLogic = $this->assignmentLogic(false);
        $stub = str_replace('{{ mountLogic }}', $mountLogic, $stub);

        $updateLogic = $this->assignmentLogic(true);
        $stub = str_replace('{{ updateLogic }}', $updateLogic, $stub);

        $stub = str_replace('{{ title }}', $this->getModelTitle($this->modelName), $stub);

        $dataVariable = $this->getDataVariable($this->modelName, '_', false);
        $stub = str_replace('{{ dataVariable }}', $dataVariable, $stub);

        $stub = str_replace('{{ directoryName }}', $this->getViewDirectoryName($this->modelName), $stub);

        $editComponentFile = app_path("Livewire/{$this->modelName}") . '/Edit.php';

        file_put_contents($editComponentFile, $stub);
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
}
