<?php

namespace Tgozo\LaravelCodegen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\ServiceProvider;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\CombineMigrationsCommand;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Migration;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\SquashMigrationsCommand;
use Tgozo\LaravelCodegen\Console\Commands\CleanCommand;
use Tgozo\LaravelCodegen\Console\Commands\OpenApiCommand;
use Tgozo\LaravelCodegen\Console\Commands\PublishStubsCommand;
use Tgozo\LaravelCodegen\Console\Commands\ReverseEngineer\ReverseEngineerCommand;
use Tgozo\LaravelCodegen\Console\Commands\WizardCommand;

class CodeGenServiceProvider extends ServiceProvider
{
    protected array $commands = [
        Migration::class,
        CombineMigrationsCommand::class,
        SquashMigrationsCommand::class,
        ReverseEngineerCommand::class,
        WizardCommand::class,
        PublishStubsCommand::class,
        CleanCommand::class,
        OpenApiCommand::class,
    ];

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravelcodegen.php', 'laravelcodegen');

        $this->publishes([
            __DIR__ . '/../config/laravelcodegen.php' => config_path('laravelcodegen.php'),
        ], 'laravelcodegen-config');

        $this->publishes([
            __DIR__ . '/stubs' => base_path('resources/stubs/vendor/laravelcodegen'),
        ], 'laravelcodegen-stubs');
    }


    public function register(): void
    {
        $this->commands($this->commands);
        $this->app->singleton(Inflector::class, function () {
            return InflectorFactory::create()->build();
        });
    }
}
