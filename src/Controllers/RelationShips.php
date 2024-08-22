<?php

namespace Tgozo\LaravelCodegen\Controllers;

class RelationShips
{

    const RELATIONSHIP_MAPPER = [
        'belongsto' => [
            'returnName' => 'BelongsTo',
            'methodName' => 'belongsTo',
            'plural' => false
        ],
        'belongstomany' => [
            'returnName' => 'BelongsToMany',
            'methodName' => 'belongsToMany',
            'plural' => true
        ],
        'hasmany' => [
            'returnName' => 'HasMany',
            'methodName' => 'hasMany',
            'plural' => true
        ],
        'hasmanythrough' => [
            'returnName' => 'HasManyThrough',
            'methodName' => 'hasManyThrough',
            'plural' => true
        ],
        'hasone' => [
            'returnName' => 'HasOne',
            'methodName' => 'hasOne',
            'plural' => false
        ],
        'hasonethrough' => [
            'returnName' => 'HasOneThrough',
            'methodName' => 'hasOneThrough',
            'plural' => false
        ],
        'morphmany' => [
            'returnName' => 'MorphMany',
            'methodName' => 'morphMany',
            'plural' => true
        ],
        'morphone' => [
            'returnName' => 'MorphOne',
            'methodName' => 'morphOne',
            'plural' => false
        ],
        'morphto' => [
            'returnName' => 'MorphTo',
            'methodName' => 'morphTo',
            'plural' => false
        ],
        'morphtomany' => [
            'returnName' => 'MorphToMany',
            'methodName' => 'morphToMany',
            'plural' => true
        ],
    ];

    const ARGUMENTS_REQUIRED = [
        'belongsto' => [
            'min' => 1,
            'max' => 4
        ],
        'belongstomany' => [
            'min' => 1,
            'max' => 7
        ],
        'hasmany' => [
            'min' => 1,
            'max' => 3
        ],
        'hasmanythrough' => [
            'min' => 2,
            'max' => 6
        ],
        'hasone' => [
            'min' => 1,
            'max' => 3
        ],
        'hasonethrough' => [
            'min' => 2,
            'max' => 6
        ],
        'morphmany' => [
            'min' => 2,
            'max' => 5
        ],
        'morphone' => [
            'min' => 2,
            'max' => 5
        ],
        'morphto' => [
            'min' => 0,
            'max' => 4
        ],
        'morphtomany' => [
            'min' => 2,
            'max' => 9
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

        $action = "singularize";
        if (self::RELATIONSHIP_MAPPER[$relationType]['plural']){
            $action = "pluralize";
        }
        $relationName = $this->package->{$action}($this->package->str_to_lower($relationModel));

        $methodName = self::RELATIONSHIP_MAPPER[$relationType]['methodName'];
        $returnName = self::RELATIONSHIP_MAPPER[$relationType]['returnName'];

        foreach ($relationship['parameters'] as $parameter) {
            $str = ", '{$parameter}'";
        }

        $this->package->registerNamespace("Illuminate\Database\Eloquent\Relations\\$returnName");

        $relationString = "\n\tpublic function {$relationName}(): $returnName" . "\n\t{\n\t\t";
        $relationString .= "return \$this->{$methodName}({$relationModel}::class{$str});" . "\n\t}\n";

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

    public function generateRelationships(): void
    {
        $str = $this->getRelationsString();
        $file_to_use = app_path('Models') . "/{$this->modelName}.php";

        if (!file_exists($file_to_use)){
            $this->package->warn("Relationships not generated because the file {$file_to_use} doesn't exist");
            return;
        }

        $file_contents = file_get_contents($file_to_use);
        $class_pos = strpos($file_contents, "class {$this->modelName} extends Model");
        $open_brace_pos = strpos($file_contents, '{', $class_pos);
        $close_brace_pos = $this->package->findClosingBrace($file_contents, $open_brace_pos + 1);

        $new_file_contents = substr_replace($file_contents, "\t$str\n", $close_brace_pos, 0);

        $new_file_contents = $this->package->addNamespaces($new_file_contents, false);

        file_put_contents($file_to_use, $new_file_contents);

        $this->package->info("Relationships generated successfully in {$file_to_use}.");
    }
}
