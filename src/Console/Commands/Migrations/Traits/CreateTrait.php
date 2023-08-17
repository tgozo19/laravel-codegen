<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait CreateTrait
{
    public function handle_create_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern);

        $created_migration_name = $this->createMigration($name, $fields);

        $this->info("Migration [$created_migration_name] created successfully.");

        if ($this->option('m')){
            $modelName = $this->singularize(ucfirst($this->getTableName($name)));
            $created_model_name = $this->createModel($modelName, $fields);
            $this->info("Model [$created_model_name] created successfully.");
        }

        if ($this->option('c')){
            if (isset($modelName)){
                $controllerName = $this->controller_name_from_model($modelName);
                $created_controller_name = $this->createController($controllerName, $modelName, $fields, "standard");
                $this->info("Controller [$created_controller_name] created successfully.");
                $this->info("6 routes created in routes/web.php file.");
                $this->info("4 views created in resources/views/{$this->str_to_lower($modelName)} directory.");
            }
        }
    }
}
