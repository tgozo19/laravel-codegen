<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait AddColumnTrait
{
    public function handle_add_column_to_command($name, $pattern = "create"): void
    {
        $fields = $this->getFields($pattern, 1);

        $migration_name = $this->createMigration($name, $fields, "add_column");

        $this->info("Migration [$migration_name] created successfully.");
    }
}
