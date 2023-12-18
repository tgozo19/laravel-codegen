<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

use Tgozo\LaravelCodegen\Controllers\Livewire;

trait CreateTrait
{
    public function handle_create_command($name, $pattern = "create"): void
    {
        $table_name = $this->getTableName($name);

        $table_name_to_be_passed = null;
        if (array_key_exists($this->str_to_lower($table_name), $this->exceptions)){
            $table_name_to_be_passed = $this->exceptions[$this->str_to_lower($table_name)];
        }

        $fields = $this->getFields($pattern);

        $created_migration_name = $this->createMigration($name, $fields, "create", $table_name_to_be_passed);

        $this->info("Migration [$created_migration_name] created successfully.");

        $modelName = $this->singularize($this->format_to_get_model_name($table_name));

        if (($this->option('m') || $this->option('all')) && !in_array('m', $this->option_exceptions)){
            $created_model_name = $this->createModel($modelName, $fields, "standard", $table_name_to_be_passed);
            $this->info("Model [$created_model_name] created successfully.");
        }

        if (($this->option('c') || $this->option('all'))  && !in_array('c', $this->option_exceptions)){
            $controllerName = $this->controller_name_from_model($modelName);
            $created_controller_name = $this->createController($controllerName, $modelName, $fields, "standard");
            $this->info("Controller [$created_controller_name] created successfully.");
            $this->info("6 routes created in routes/web.php file.");
            $this->info("4 views created in resources/views/{$this->str_to_lower($modelName)} directory.");
        }

        if (($this->option('l') || $this->option('all'))  && !in_array('l', $this->option_exceptions)){
            $livewire = new Livewire($this, $modelName, $fields);
            $livewire->createComponents();
//            $this->info("6 routes created in routes/web.php file.");
        }

        if (($this->option('s') || $this->option('all'))  && !in_array('s', $this->option_exceptions)){
            $this->create_seeder($modelName, $fields);
        }

        if (($this->option('f') || $this->option('all'))  && !in_array('f', $this->option_exceptions)){
            $this->create_factory($modelName, $fields);
        }

        if (($this->option('p') || $this->option('all'))  && !in_array('p', $this->option_exceptions)){
            $this->create_tests($modelName, $fields);
        }
    }
}
