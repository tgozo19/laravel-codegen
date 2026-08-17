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

        if ($this->hasOption('requests') && $this->option('requests')) {
            $domain = $this->hasOption('domain') ? $this->option('domain') : null;
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateFormRequests($modelName, $fields, $domain, $isDryRun);
        }

        if ($this->hasOption('enum') && $this->option('enum')) {
            $enumString = $this->option('enum');
            $enumParts = explode(':', $enumString);
            $enumName = $enumParts[0];
            $cases = isset($enumParts[1]) ? explode(',', $enumParts[1]) : ['draft', 'published', 'archived'];
            $domain = $this->hasOption('domain') ? $this->option('domain') : null;
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateEnum($enumName, $cases, $domain, $isDryRun);
        }

        if ($this->hasOption('inertia') && $this->option('inertia')) {
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateInertiaComponents($modelName, $fields, $isDryRun);
        }

        if ($this->hasOption('types') && $this->option('types')) {
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateTypeScriptDefinition($modelName, $fields, $isDryRun);
        }

        if ($this->hasOption('events') && $this->option('events')) {
            $domain = $this->hasOption('domain') ? $this->option('domain') : null;
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateDomainEvents($modelName, $domain, $isDryRun);
        }

        if ($this->hasOption('react') && $this->option('react')) {
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateInertiaReactComponents($modelName, $fields, $isDryRun);
        }

        if ($this->hasOption('repository') && $this->option('repository')) {
            $domain = $this->hasOption('domain') ? $this->option('domain') : null;
            $isDryRun = $this->hasOption('dry-run') && $this->option('dry-run');
            $this->generateRepositoryPattern($modelName, $domain, $isDryRun);
        }
    }
}
