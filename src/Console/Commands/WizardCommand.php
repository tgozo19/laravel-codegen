<?php

namespace Tgozo\LaravelCodegen\Console\Commands;

use Illuminate\Console\Command;
use Tgozo\LaravelCodegen\Console\BaseTrait;

class WizardCommand extends Command
{
    use BaseTrait;

    protected $signature = 'codegen:wizard';

    protected $description = 'Interactive terminal scaffolding wizard for Laravel CodeGen';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("     🚀 Welcome to Laravel CodeGen Wizard!        ");
        $this->info("==================================================");

        $action = $this->promptSelect(
            label: 'What action would you like to perform?',
            options: [
                'module' => 'Generate Application Module (Migration, Model, Controller, etc.)',
                'reverse' => 'Reverse Engineer Existing Database Tables',
                'combine' => 'Combine Migrations',
                'squash' => 'Squash Table Migrations',
                'openapi' => 'Generate OpenAPI Specification',
                'clean' => 'Clean Up / Remove Module Artifacts',
            ],
            default: 'module'
        );

        return match ($action) {
            'module' => $this->handleModuleGeneration(),
            'reverse' => $this->handleReverseEngineering(),
            'combine' => $this->handleCombineMigrations(),
            'squash' => $this->handleSquashMigrations(),
            'openapi' => $this->handleOpenApi(),
            'clean' => $this->handleCleanModule(),
            default => Command::SUCCESS,
        };
    }

    protected function handleModuleGeneration(): int
    {
        $migrationName = $this->promptText(
            label: 'Enter the migration name',
            placeholder: 'e.g. create_posts_table',
            validate: fn ($value) => empty(trim($value)) ? 'Migration name is required' : null
        );

        $generateAll = $this->promptConfirm(
            label: 'Generate all components (Model, Controller, Factory, Seeder, Blade, Tests, Livewire)?',
            default: true
        );

        $options = [];
        if ($generateAll) {
            $options['--all'] = true;
        } else {
            if ($this->promptConfirm('Generate Model (-m)?', true)) $options['-m'] = true;
            if ($this->promptConfirm('Generate Controller (-c)?', true)) $options['-c'] = true;
            if ($this->promptConfirm('Generate Factory (-f)?', true)) $options['-f'] = true;
            if ($this->promptConfirm('Generate Seeder (-s)?', true)) $options['-s'] = true;
            if ($this->promptConfirm('Generate Blade Views (-b)?', true)) $options['-b'] = true;
            if ($this->promptConfirm('Generate Pest PHP Tests (-p)?', true)) $options['-p'] = true;
            if ($this->promptConfirm('Generate Livewire Components (-l)?', false)) $options['-l'] = true;
        }

        if ($this->promptConfirm('Generate Form Requests (--requests)?', true)) {
            $options['--requests'] = true;
        }

        if ($this->promptConfirm('Generate Inertia.js Vue 3 Components (--inertia)?', false)) {
            $options['--inertia'] = true;
        }

        if ($this->promptConfirm('Generate Inertia.js React TSX Components (--react)?', false)) {
            $options['--react'] = true;
        }

        if ($this->promptConfirm('Generate Repository Pattern classes (--repository)?', false)) {
            $options['--repository'] = true;
        }

        if ($this->promptConfirm('Generate TypeScript Interface Definitions (--types)?', false)) {
            $options['--types'] = true;
        }

        if ($this->promptConfirm('Generate Domain Events & Listeners (--events)?', false)) {
            $options['--events'] = true;
        }

        if ($this->promptConfirm('Include SoftDeletes controller actions (--soft-deletes-actions)?', false)) {
            $options['--soft-deletes-actions'] = true;
        }

        $enumInput = $this->promptText('Specify Backed Enum if needed (--enum=Name:case1,case2)', 'e.g. Status:draft,published');
        if (!empty(trim($enumInput))) {
            $options['--enum'] = trim($enumInput);
        }

        $domainInput = $this->promptText('Specify Domain/DDD namespace (--domain=DomainName)', 'e.g. Blog');
        if (!empty(trim($domainInput))) {
            $options['--domain'] = trim($domainInput);
        }

        if ($this->promptConfirm('Dry Run simulation mode (--dry-run)?', false)) {
            $options['--dry-run'] = true;
        }

        if ($this->promptConfirm('Force overwrite existing files if present?', false)) {
            $options['--force'] = true;
        }

        $arguments = array_merge(['name' => $migrationName], $options);

        return $this->call('make:codegen-migration', $arguments);
    }

    protected function handleSquashMigrations(): int
    {
        $tableName = $this->promptText(
            label: 'Enter table name to squash migrations for',
            placeholder: 'e.g. posts',
            validate: fn ($value) => empty(trim($value)) ? 'Table name is required' : null
        );

        return $this->call('make:codegen-squash', ['name' => $tableName]);
    }

    protected function handleOpenApi(): int
    {
        return $this->call('codegen:openapi');
    }

    protected function handleReverseEngineering(): int
    {
        $mode = $this->promptSelect(
            label: 'What artifacts would you like to reverse engineer?',
            options: [
                'all' => 'Both Models and Migrations',
                'models' => 'Models Only',
                'migrations' => 'Migrations Only',
            ],
            default: 'all'
        );

        $tablesInput = $this->promptText(
            label: 'Specific table names (comma-separated, leave empty for all tables)',
            placeholder: 'e.g. users,posts,comments'
        );

        $options = [];
        if ($mode === 'all') {
            $options['--all'] = true;
        } elseif ($mode === 'models') {
            $options['--models'] = true;
        } else {
            $options['--migrations'] = true;
        }

        if (!empty(trim($tablesInput))) {
            $tables = array_map('trim', explode(',', $tablesInput));
            $options['--tables'] = $tables;
        }

        if ($this->promptConfirm('Force overwrite existing files?', false)) {
            $options['--force'] = true;
        }

        return $this->call('codegen:reverse-engineer', $options);
    }

    protected function handleCombineMigrations(): int
    {
        $tableName = $this->promptText(
            label: 'Enter table name to combine migrations for',
            placeholder: 'e.g. posts',
            validate: fn ($value) => empty(trim($value)) ? 'Table name is required' : null
        );

        return $this->call('make:codegen-combine-migrations', ['name' => $tableName]);
    }

    protected function handleCleanModule(): int
    {
        $modelName = $this->promptText(
            label: 'Enter model/module name to clean up',
            placeholder: 'e.g. Post',
            validate: fn ($value) => empty(trim($value)) ? 'Model name is required' : null
        );

        return $this->call('codegen:clean', ['model' => $modelName]);
    }
}
