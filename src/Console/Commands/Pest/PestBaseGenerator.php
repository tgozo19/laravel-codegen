<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Pest;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Console\BaseTrait;
use Tgozo\LaravelCodegen\Console\Commands\Pest\Traits\MethodsTrait;

class PestBaseGenerator extends Command
{
    use MethodsTrait, BaseTrait;
}
