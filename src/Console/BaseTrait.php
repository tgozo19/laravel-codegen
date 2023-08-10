<?php

namespace Tgozo\LaravelCodegen\Console;

use Doctrine\Inflector\Inflector;

trait BaseTrait
{
    public function codegen_path($path): string
    {
        return dirname(__DIR__, 1) . "/{$path}";
    }

    public function snakeToCamelPlural($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return Inflector::pluralize($string);
    }

    public function snakeToCamelSingular($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return Inflector::singularize($string);
    }

    public function formatFile($file): void
    {
        $contents = file_get_contents($file);
        $contents = preg_replace("/\n\s*\n/", "\n\n", $contents);
        file_put_contents($file, $contents);
    }

    public function intersectArrays($arr1, $arr2): array
    {
        return array_values(array_intersect($arr1, $arr2));
    }

}
