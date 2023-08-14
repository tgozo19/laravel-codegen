<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait CreateTrait
{
    public function handle_create_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern);

        $this->createMigration($name, $fields);

        if ($this->option('m')){
            $modelName = $this->singularize(ucfirst($this->getTableName($name)));
            $this->createModel($modelName, $fields);
            $this->info('Created Model: ' . $modelName);
        }

        if ($this->option('c')){
            if (isset($modelName)){
                $controllerName = $this->controller_name_from_model($modelName);
                $this->createController($controllerName, $modelName, $fields, "standard");
            }
        }

        $this->info('Created migration: ' . $name);
    }
}
