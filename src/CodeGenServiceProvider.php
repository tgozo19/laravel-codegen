<?php

namespace Tgozo\LaravelCodegen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\ServiceProvider;
use Tgozo\LaravelCodegen\Console\Commands\Migrations\Migration;

class CodeGenServiceProvider extends ServiceProvider
{
    protected array $commands = [
        Migration::class,
    ];

    public function register(): void
    {
        $this->commands($this->commands);
        $this->app->singleton(Inflector::class, function () {
            return InflectorFactory::create()->build();
        });
    }
}
