<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait CreateTrait
{
    public function handle_create_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern);

        $created_migration_name = $this->createMigration($name, $fields);

        $this->info("Migration [$created_migration_name] created successfully.");

        $table_name = $this->getTableName($name);

        $modelName = $this->singularize($this->format_to_get_model_name($table_name));

        if ($this->option('m') || $this->option('all')){
            $created_model_name = $this->createModel($modelName, $fields);
            $this->info("Model [$created_model_name] created successfully.");
        }

        if ($this->option('c') || $this->option('all')){
            $controllerName = $this->controller_name_from_model($modelName);
            $created_controller_name = $this->createController($controllerName, $modelName, $fields, "standard");
            $this->info("Controller [$created_controller_name] created successfully.");
            $this->info("6 routes created in routes/web.php file.");
            $this->info("4 views created in resources/views/{$this->str_to_lower($modelName)} directory.");
        }

        if ($this->option('s') || $this->option('all')){
            $this->create_seeder($modelName, $fields);
        }

        if ($this->option('f') || $this->option('all')){
            $this->create_factory($modelName, $fields);
        }

        if ($this->option('p') || $this->option('all')){
            $this->create_tests($modelName, $fields);
        }
    }
}
