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
}
