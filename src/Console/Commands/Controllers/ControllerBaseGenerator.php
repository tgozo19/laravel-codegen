<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Controllers;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Console\BaseTrait;
use Tgozo\LaravelCodegen\Console\Commands\Controllers\Traits\AttributesTrait;
use Tgozo\LaravelCodegen\Console\Commands\Controllers\Traits\MethodsTrait;

class ControllerBaseGenerator extends Command
{
    use AttributesTrait, MethodsTrait, BaseTrait;
}
