<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations;

class Migration extends MigrationBaseGenerator
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codegen:migration {name?} {--m|m} {--c|c}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $name = $this->getMigrationName();

        $fields = $this->getFields();

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
