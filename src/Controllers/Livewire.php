<?php

namespace Tgozo\LaravelCodegen\Controllers;

class Livewire
{

    const COMPONENT_NAMES = ['View', 'Show', 'Edit', 'Create', 'Store', 'Update', 'Delete'];

    protected array $pretendMessages = ['success' => [], 'failures' => []];

    public function __construct(protected readonly mixed $package, protected string $modelName, protected readonly string $action = 'create')
    {
    }

    public function __invoke(): void
    {
        $this->createComponents();
    }

    private function pretend(bool $verbose): void
    {
        $this->createComponents();
    }

    public function createComponents(): void
    {
        // modelName => Dog
        foreach (self::COMPONENT_NAMES as $COMPONENT_NAME) {
            $this->createComponent($COMPONENT_NAME);
        }

//        foreach ($ as $item) {
//
//        }
    }

    public function checkIfComponentExists(string $componentName): bool
    {
        $component_path = app_path("Livewire") . "{$this->modelName}/{$componentName}";
        return file_exists($component_path);
    }

    public function createComponent(string $componentName): void
    {
        $exists = $this->checkIfComponentExists($componentName);
        if ($exists){
            $this->pretendMessages['failures'][] = $componentName;
        }else{
            $this->pretendMessages['success'][] = $componentName;
        }

        if ($this->action === 'pretend'){
            // show messages and return

        }

        if ($this->action === 'create'){
            // check
            $this->package->info('kkkk');
//        $a = shell_exec('php artisan make:model Taku');
//        dump($a);
        }
    }
}
