<?php

namespace Tgozo\LaravelCodegen\Controllers;

use Exception;
use Tgozo\LaravelCodegen\Console\BaseTrait;

class Routes
{
    use BaseTrait;

    const LIVEWIRE_COMPONENT_NAMES = ['View', 'Show', 'Edit', 'Create'];

    const LIVEWIRE_COMPONENT_NAMES_NAMESPACE = ['view' => 'plural', 'show' => 'singular', 'edit' => 'singular', 'create' => 'singular'];

    const LIVEWIRE_COMPONENT_REQUEST_TYPE = ['view' => 'get', 'show' => 'get', 'edit' => 'get', 'create' => 'get'];

    const LIVEWIRE_IS_COMPONENT_PREFIXED = ['view' => false, 'show' => false, 'edit' => true, 'create' => true];

    const LIVEWIRE_COMPONENT_NEEDS_ID = ['view' => false, 'show' => true, 'edit' => true, 'create' => false];

    /**
     * @throws Exception
     */
    public function __construct(protected readonly mixed $package, protected readonly string $modelName, protected readonly array $fields)
    {

    }

    public function isPlural(string $COMPONENT_NAME): bool
    {
        return self::LIVEWIRE_COMPONENT_NAMES_NAMESPACE[$this->str_to_lower($COMPONENT_NAME)] === 'plural';
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

    public function getRoutesString(): string
    {
        $str = "";

        foreach (self::LIVEWIRE_COMPONENT_NAMES as $COMPONENT_NAME) {
            $plural = $this->isPlural($COMPONENT_NAME);
            if ($plural){
                $route_model_string = $this->str_to_lower($this->getDataVariable($this->pluralize($this->modelName), '-'));
            }else{
                $route_model_string = $this->str_to_lower($this->getDataVariable($this->singularize($this->modelName), '-'));
            }
            $as = $this->return_as($COMPONENT_NAME);
            $request_type = self::LIVEWIRE_COMPONENT_REQUEST_TYPE[$this->str_to_lower($COMPONENT_NAME)];
            $is_prefixed = self::LIVEWIRE_IS_COMPONENT_PREFIXED[$this->str_to_lower($COMPONENT_NAME)];
            $needs_id = self::LIVEWIRE_COMPONENT_NEEDS_ID[$this->str_to_lower($COMPONENT_NAME)];

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

        foreach (self::LIVEWIRE_COMPONENT_NAMES as $COMPONENT_NAME) {
            $as = $this->return_as($COMPONENT_NAME);
            $name_space = "use App\Livewire\\$this->modelName\\$COMPONENT_NAME as {$as};";

            $new_file = file_get_contents(base_path($file_path));

            if (str_contains($new_file, $name_space)){
                continue;
            }

            $replace_string = "<?php" . PHP_EOL;
            $replace_string .= $name_space;

            $new_file_contents = str_replace("<?php", $replace_string, $new_file);

            file_put_contents(base_path($file_path), $new_file_contents);
        }

        $file = fopen(base_path($file_path), 'a+');
        fwrite($file, PHP_EOL . $routesString);
        fclose($file);
    }
}
