<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations;

use Symfony\Component\Console\Output\OutputInterface;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\AddColumnsTrait;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\AddColumnTrait;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\CreateTrait;
use Tgozo\LaravelCodegen\Console\Commands\Seeders\Traits\MethodsTrait as SeederMethodsTrait;
use Tgozo\LaravelCodegen\Console\Commands\Factories\Traits\MethodsTrait as FactoryMethodsTrait;
use Tgozo\LaravelCodegen\Console\Commands\Models\Traits\AttributesTrait as ModelAttributesTraits;
use Tgozo\LaravelCodegen\Controllers\Livewire;

class Migration extends MigrationBaseGenerator
{
    use CreateTrait, AddColumnTrait, AddColumnsTrait, SeederMethodsTrait, FactoryMethodsTrait, ModelAttributesTraits;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'make:codegen-migration {name?} {--m|m} {--c|c} {--b|b} {--r|r} {--s|s} {--f|f} {--p|p} {--l|l} {--except=} {--relates=} {--all|all} {--force|force}';

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
        $this->validate_except();

        $this->validateRelations();

        $name = $this->getMigrationName();

        $this->check_migration_existence($name);

        $pattern = $this->getPattern($name);

        $this->perform_checks("migration_route", $pattern, $name);

        if (!method_exists($this, "handle_{$pattern}command")){
            $this->info("Command for pattern {$pattern} doesn't exist");
            exit;
        }
        $this->{"handle_{$pattern}command"}($name, $pattern);
    }
}
