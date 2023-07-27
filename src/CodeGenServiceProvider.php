<?php

namespace Tgozo\CodeGenerator;

use Illuminate\Support\ServiceProvider;

class CodeGenServiceProvider extends ServiceProvider
{
    protected array $commands = [
        Console\Commands\Migration::class,
    ];

    public function register(): void
    {
        $this->commands($this->commands);
    }
}
