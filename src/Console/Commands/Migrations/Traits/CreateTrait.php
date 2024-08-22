<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

use Exception;
use Tgozo\LaravelCodegen\Console\BaseTrait;
use Tgozo\LaravelCodegen\Controllers\Livewire;
use Tgozo\LaravelCodegen\Controllers\RelationShips;

trait CreateTrait
{
    /**
     * @throws Exception
     */
    public function handle_create_command($name, $pattern = "create"): void
    {
        if ($this->checkOption('l',true)){
            Livewire::verifyInstallation(true);
        }

        $table_name = $this->getTableName($name);

        $table_name_to_be_passed = null;
        if (array_key_exists($this->str_to_lower($table_name), $this->exceptions)){
            $table_name_to_be_passed = $this->exceptions[$this->str_to_lower($table_name)];
        }

        $fields = $this->getFields($pattern);

        $created_migration_name = $this->createMigration($name, $fields, "create", $table_name_to_be_passed);

        $this->info("Migration [$created_migration_name] created successfully.");

        $modelName = $this->singularize($this->format_to_get_model_name($table_name));

        if ($this->checkOption('m', true)){
            $created_model_name = $this->createModel($modelName, $fields, "standard", $table_name_to_be_passed);
            $this->info("Model [$created_model_name] created successfully.");
        }

        if ($this->checkOption('c', true)){
            $controllerName = $this->controller_name_from_model($modelName);
            $created_controller_name = $this->createController($controllerName, $modelName, $fields, "standard");
            $this->info("Controller [$created_controller_name] created successfully.");
            $this->info("6 routes created in routes/web.php file.");
            $this->info("4 views created in resources/views/{$this->str_to_lower($modelName)} directory.");
        }

        if ($this->checkOption('l', true)){
            $livewire = new Livewire($this, $modelName, $fields);
            $livewire->createComponents();
        }

        if ($this->checkOption('s', true)){
            $this->create_seeder($modelName, $fields);
        }

        if ($this->checkOption('f', true)){
            $this->create_factory($modelName, $fields);
        }

        if ($this->checkOption('p', true)){
            $this->create_tests($modelName, $fields);
        }

        if ($this->checkOption('relates', true)){
            $relationships = new RelationShips($this, $modelName, $this->relationships);
            $relationships->generateRelationships();
        }
    }
}
