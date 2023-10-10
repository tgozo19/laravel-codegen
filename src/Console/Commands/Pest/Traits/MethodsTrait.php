<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Pest\Traits;

trait MethodsTrait
{
    public function functions_string(): string
    {
        $str = "use function Pest\Laravel\{get};" . PHP_EOL;
        $str .= "use function Pest\Laravel\{post};" . PHP_EOL;
        $str .= "use function Pest\Laravel\{delete};" . PHP_EOL;
        return $str;
    }

    public function classes_string($model_name, $fields): string
    {
        $str = "use App\Models\\$model_name;" . PHP_EOL;
        $has_hash = !empty(array_filter($fields, function ($field){return str($field['name'])->contains('password');}));
        if ($has_hash){
            $str .= "use Illuminate\Support\Facades\Hash;" . PHP_EOL;
        }
        $str .= "use Illuminate\Foundation\Testing\DatabaseTransactions;" . PHP_EOL;
        $str .= "use Illuminate\Support\Facades\Schema;" . PHP_EOL;
        return $str;
    }

    public function uses_string(): string
    {
        return "uses(DatabaseTransactions::class);" . PHP_EOL;
    }

    public function overall_view_test($model_name): string
    {
        $str = "test('all {$this->str_to_lower($this->pluralize($model_name))} can be viewed', function () {" . PHP_EOL;
        $str .= "\tget(route('view-{$this->str_to_lower($this->pluralize($model_name))}'))->assertOk();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function specific_view_test($model_name): string
    {
        $str = "test('a specific {$this->str_to_lower($this->singularize($model_name))} can be viewed', function () {" . PHP_EOL;
        $str .= "\t\${$this->str_to_lower($this->singularize($model_name))} = {$model_name}::first();" . PHP_EOL;
        $str .= "\tget(route('show-{$this->str_to_lower($this->singularize($model_name))}', ['id' => \${$this->str_to_lower($this->singularize($model_name))}->id]))->assertOk();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function edit_form_test($model_name): string
    {
        $str = "test('edit {$this->str_to_lower($this->singularize($model_name))} form can be viewed', function () {" . PHP_EOL;
        $str .= "\t\${$this->str_to_lower($this->singularize($model_name))} = {$model_name}::first();" . PHP_EOL;
        $str .= "\tget(route('edit-{$this->str_to_lower($this->singularize($model_name))}', ['id' => \${$this->str_to_lower($this->singularize($model_name))}->id]))->assertOk();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function create_form_test($model_name): string
    {
        $str = "test('create {$this->str_to_lower($this->singularize($model_name))} form can be viewed', function () {" . PHP_EOL;
        $str .= "\tget(route('create-{$this->str_to_lower($this->singularize($model_name))}'))->assertOk();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function saving_test($model_name, $fields): string
    {
        $str = "test('a {$this->str_to_lower($this->singularize($model_name))} can be saved', function () {" . PHP_EOL;
        $str .= "\t\$postData = {$this->get_faker_string($fields)}" . PHP_EOL;
        $str .= "\tpost(route('store-{$this->str_to_lower($this->singularize($model_name))}'), \$postData);" . PHP_EOL;

        $str .= "\t\$post_data_keys = array_keys(\$postData);" . PHP_EOL . PHP_EOL;
        $str .= "\t\$post_data_key = \$post_data_keys[0];" . PHP_EOL;
        $str .= "\t\$post_data_field_value = \$postData[\$post_data_keys[0]];" . PHP_EOL;

        $str .= "\texpect({$model_name}::where(\$post_data_key, \$post_data_field_value)->exists())->toBeTrue();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function deleting_test($model_name): string
    {
        $str = "test('a {$this->str_to_lower($this->singularize($model_name))} can be deleted', function () {" . PHP_EOL;
        $str .= "\t\${$this->str_to_lower($this->singularize($model_name))} = {$model_name}::factory()->create();" . PHP_EOL;
        $str .= "\t\$response = delete(route('delete-{$this->str_to_lower($this->pluralize($model_name))}', ['ids' => \${$this->str_to_lower($this->singularize($model_name))}->id]));" . PHP_EOL;

        $str .= "\texpect({$model_name}::whereId(\${$this->str_to_lower($this->singularize($model_name))}->exists()))->not()->toBeTrue();" . PHP_EOL;
        $str .= "});" . PHP_EOL;
        return $str;
    }

    public function tests_buffer($model_name, $fields): string
    {
        $str = $this->overall_view_test($model_name);
        $str .= $this->specific_view_test($model_name);
        $str .= $this->edit_form_test($model_name);
        $str .= $this->create_form_test($model_name);
        $str .= $this->saving_test($model_name, $fields);
        $str .= $this->deleting_test($model_name);

        return $str;
    }



    public function create_tests($model_name, $fields): void
    {
        $test_name = $this->singularize($model_name) . 'Test';
        $test_file = base_path('tests/Feature') . "/{$test_name}.php";


        $functions_string = $this->functions_string();
        $classes_string = $this->classes_string($model_name, $fields);
        $uses_string = $this->uses_string();

        $tests_string = $this->tests_buffer($model_name, $fields);

        $stub = $this->load_stub('test.pest');

        $stub = str_replace("{{ functions }}", $functions_string, $stub);
        $stub = str_replace("{{ classes }}", $classes_string, $stub);
        $stub = str_replace("{{ uses }}", $uses_string, $stub);

        $stub = str_replace("{{ model_name }}", $model_name, $stub);
        $stub = str_replace("{{ lower_model_name }}", $this->str_to_lower($model_name), $stub);
        $stub = str_replace("{{ lower_plural_model_name }}", $this->pluralize($this->str_to_lower($model_name)), $stub);

        $stub = str_replace("{{ tests }}", $tests_string, $stub);

        file_put_contents($test_file, $stub);

        $this->info("Test [$test_file] created successfully.");
    }
}
