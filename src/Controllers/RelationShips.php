<?php

namespace Tgozo\LaravelCodegen\Controllers;

class RelationShips
{
    const RELATIONSHIPS = [
        'belongsTo',
        'belongsToMany',
        'hasMany',
        'hasManyThrough',
        'hasOne',
        'hasOneOrMany',
        'hasOneThrough',
        'morphMany',
        'morphOne',
        'morphOneOrMany',
        'morphPivot',
        'morphTo',
        'morphToMany',
    ];

    const ARGUMENTS_REQUIRED = [
        'belongsTo' => [
            'min' => 1,
            'max' => 4
        ]
    ];

    public function __construct(protected readonly mixed $package, protected readonly string $modelName, protected readonly array $relationships)
    {
    }

    public function getRelation($relationship): string
    {
        $str = '';
        $relationType = $relationship['type'];
        $relationModel = $relationship['model'];
        $relationName = $this->package->singularize($this->package->str_to_lower($relationModel));
        foreach ($relationship['parameters'] as $parameter) {
            $str = ", '{$parameter}'";
        }

        $relationString = "\n\tpublic function {$relationName}(): $relationType" . "\n\t{\n\t\t";
        $relationString .= "return \$this->{$relationType}({$relationModel}::class{$str});" . "\n\t}\n";

        return $relationString;
    }

    protected function getRelationsString(): string
    {
        $str = '';
        foreach ($this->relationships as $relationship) {
            $str .= $this->getRelation($relationship);
        }

        return trim($str);
    }

    public function generateRelationships()
    {
        $str = $this->getRelationsString();
        $file_to_use = app_path('Models') . "/{$this->modelName}.php";
        $file_contents = file_get_contents($file_to_use);
        $class_pos = strpos($file_contents, "class {$this->modelName} extends Model");
        $open_brace_pos = strpos($file_contents, '{', $class_pos);
        $close_brace_pos = $this->package->findClosingBrace($file_contents, $open_brace_pos + 1);

        $new_file_contents = substr_replace($file_contents, "\t$str\n", $close_brace_pos, 0);
        file_put_contents($file_to_use, $new_file_contents);

        $this->package->info("Relationships generated successfully in {$file_to_use}.");
    }
}
