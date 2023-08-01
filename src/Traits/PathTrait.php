<?php

namespace Tgozo\LaravelCodegen\Traits;

trait PathTrait
{
    public function codegen_path($path): string
    {
        return dirname(__DIR__, 1) . "/{$path}";
    }
}
