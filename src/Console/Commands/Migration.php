<?php

namespace Tgozo\CodeGenerator\Console\Commands;

class Migration extends MigrationBaseGenerator
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codegen:migration {name?} {--with-fields}';

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
    public function handle()
    {
        $name = $this->getMigrationName();

        $fields = [];

        if ($this->option('with-fields')) {
            $fields = $this->getFields();
        }

        $this->createMigration($name, $fields);

        $this->info('Creating migration: ' . $name);
    }
}
