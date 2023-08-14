<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait AddColumnsTrait
{
    public function handle_add_columns_to_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern);

        $migration_name = $this->createMigration($name, $fields, "add_columns");

        $this->info("Migration [$migration_name] created successfully.");
    }
}
