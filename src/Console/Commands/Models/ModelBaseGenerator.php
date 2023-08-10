<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Models;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Console\BaseTrait;
use Tgozo\LaravelCodegen\Console\Commands\Models\Traits\MethodsTrait as ModelMethodsTrait;

class ModelBaseGenerator extends Command
{
    use ModelMethodsTrait, BaseTrait;
}
