<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Models\Traits;

trait MethodsTrait
{
    use AttributesTrait;
    public function getModelStubName($name)
    {
        return $this->model_stub_names[$name];
    }

    public function getFieldNames($fields): array
    {
        $passed_fields = [];
        foreach ($fields as $field) {
            $passed_fields[] = $field['name'];
        }
        return $passed_fields;
    }

    public function getHiddenString($fields): string
    {
        $passed_fields = $this->getFieldNames($fields);
        $common_fields = $this->intersectArrays($passed_fields, $this->should_be_hidden);
        $hidden = 'protected $hidden = [' . PHP_EOL;

        foreach ($common_fields as $common_field) {
            $hidden .= "\t\t" . "'{$common_field}'," . PHP_EOL;
        }
        $hidden .= "\t];";
        return $hidden;
    }

    public function getCastsString($fields): string
    {
        $passed_fields = $this->getFieldNames($fields);
        $common_fields = $this->intersectArrays($passed_fields, array_keys($this->should_be_casted));
        $hidden = 'protected $casts = [' . PHP_EOL;

        foreach ($common_fields as $common_field) {
            $hidden .= "\t\t" . "'{$common_field}' => '{$this->should_be_casted[$common_field]}'," . PHP_EOL;
        }

        $hidden .= "\t];";

        return $hidden;
    }

    public function getGuardedString($fields): string
    {
        $passed_fields = $this->getFieldNames($fields);
        $common_fields = $this->intersectArrays($passed_fields, $this->should_be_guarded);
        $hidden = 'protected $guarded = [' . PHP_EOL;

        foreach ($common_fields as $common_field) {
            $hidden .= "\t\t" . "'{$common_field}'," . PHP_EOL;
        }
        $hidden .= "\t];";
        return $hidden;
    }

    public function model_buffer(string $codegen_path, $fields, $modelName): string|array|false
    {
        $stub = file_get_contents($codegen_path);

        $name_space = "App\Models";

        $guardedString = $this->getGuardedString($fields);

        $hiddenString = $this->getHiddenString($fields);

        $castsString = $this->getCastsString($fields);

        $stub = str_replace('{{ namespace }}', $name_space, $stub);

        $stub = str_replace('{{ modelName }}', $modelName, $stub);

        $stub = str_replace('{{ $guarded }}', $guardedString, $stub);

        $stub = str_replace('{{ $hidden }}', $hiddenString, $stub);

        return str_replace('{{ $casts }}', $castsString, $stub);
    }

    public function createModel($modelName, $fields, $modelType = "standard", $table_name = null): string
    {
        $modelFile = app_path('Models') . '/' . $modelName . '.php';

        $stubName = $this->getModelStubName($modelType);
        $codegen_path = $this->codegen_path("stubs/model.{$stubName}.stub");

        $stub = $this->model_buffer($codegen_path, $fields, $modelName);

        if ($table_name){
            $table_name_string = "protected \$table = '$table_name';";

            $class_name_line_pos = strpos($stub, "class {$modelName} extends Model");
            $next_open_brace_pos = strpos($stub, '{', $class_name_line_pos);

            $stub = substr_replace($stub, PHP_EOL . "\t$table_name_string" . PHP_EOL, $next_open_brace_pos + 1, 0);
        }

        file_put_contents($modelFile, $stub);

        $this->formatFile($modelFile);

        return $modelFile;
    }
}
