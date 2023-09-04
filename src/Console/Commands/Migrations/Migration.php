<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations;

use Symfony\Component\Console\Output\OutputInterface;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\AddColumnsTrait;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\AddColumnTrait;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits\CreateTrait;
use Tgozo\LaravelCodegen\Console\Commands\Seeders\Traits\MethodsTrait;

class Migration extends MigrationBaseGenerator
{
    use CreateTrait, AddColumnTrait, AddColumnsTrait, MethodsTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codegen:migration {name?} {--m|m} {--c|c} {--s|s} {--all|all}';

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

        $pattern = $this->getPattern($name);

        if (!method_exists($this, "handle_{$pattern}command")){
            $this->info("Command for pattern {$pattern} doesn't exist");
            exit;
        }
        $this->{"handle_{$pattern}command"}($name, $pattern);
    }
}
