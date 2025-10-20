<?php

namespace Tgozo\LaravelCodegen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\ServiceProvider;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Migration;
use Tgozo\LaravelCodegen\Console\Commands\ReverseEngineer\ReverseEngineerCommand;

class CodeGenServiceProvider extends ServiceProvider
{
    protected array $commands = [
        Migration::class,
        ReverseEngineerCommand::class,
    ];

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravelcodegen.php', 'laravelcodegen');

        $this->publishes([
            __DIR__.'/../config/laravelcodegen.php' => config_path('laravelcodegen.php'),
        ]);
    }


    public function register(): void
    {
        $this->commands($this->commands);
        $this->app->singleton(Inflector::class, function () {
            return InflectorFactory::create()->build();
        });
    }
}
