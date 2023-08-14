<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait AddColumTrait
{
    public function handle_add_column_to_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern, 1);

        $this->createMigration($name, $fields, "add_column");

        $this->info('Created migration: ' . $name);
    }
}
