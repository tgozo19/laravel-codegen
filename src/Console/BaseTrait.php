<?php

namespace Tgozo\LaravelCodegen\Console;

use Doctrine\Inflector\Inflector;

trait BaseTrait
{
    public function codegen_path($path): string
    {
        return dirname(__DIR__, 1) . "/{$path}";
    }

    public function load_stub($name): string
    {
        if (str($name)->endsWith('.stub')){
            $pos = strpos($name, '.stub');
            $name = substr($name, 0, $pos);
        }

        $dir_name = dirname(__DIR__, 1) . "/stubs/{$name}.stub";
        return file_get_contents($dir_name);
    }

    public function snakeToCamelPlural($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return app(Inflector::class)->pluralize($string);
    }

    public function snakeToCamelSingular($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return app(Inflector::class)->singularize($string);
    }

    public function formatFile($file): void
    {
        $contents = file_get_contents($file);
        $contents = preg_replace("/\n\s*\n/", "\n\n", $contents);
        file_put_contents($file, $contents);
    }

    public function singularize($str): string
    {
        return app(Inflector::class)->singularize($str);
    }
    public function pluralize($str): string
    {
        return app(Inflector::class)->pluralize($str);
    }

    public function str_to_lower($str): string
    {
        return strtolower($str);
    }

    public function str_to_upper($str): string
    {
        return strtoupper($str);
    }

    public function intersectArrays($arr1, $arr2): array
    {
        return array_values(array_intersect($arr1, $arr2));
    }

    public function controller_name_from_model($modelName): string
    {
        return $modelName . "Controller";
    }

}
