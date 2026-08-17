<?php

namespace Tgozo\LaravelCodegen\Concerns;

use Tgozo\LaravelCodegen\Controllers\RelationShips;

trait Relationship
{
    public function validateRelations(): bool
    {
        $relates = $this->option('relates');
        if (empty($relates)) return true;

        $relationships = explode(',', $relates);

        foreach ($relationships as $relationship) {
            $arr = explode('->', $relationship);
            $passedRelationshipName = $arr[0];
            $relationshipName = $this->str_to_lower($passedRelationshipName);
            if (!array_key_exists($relationshipName, RelationShips::RELATIONSHIP_MAPPER)){
                $this->error("The relation {$passedRelationshipName} is not supported");
                return false;
            }
            if (count($arr) < 2) continue;

            $arguments = explode(':', $arr[1]);
            $argumentsCount = count($arguments);

            if (!array_key_exists($relationshipName, RelationShips::ARGUMENTS_REQUIRED)) continue;

            $minimumArguments = RelationShips::ARGUMENTS_REQUIRED[$relationshipName]['min'];
            if ($argumentsCount < $minimumArguments){
                $this->error("The relation {$passedRelationshipName} expects a minimum of {$minimumArguments} argument(s) not {$argumentsCount}");
                return false;
            }

            $maximumArguments = RelationShips::ARGUMENTS_REQUIRED[$relationshipName]['max'];
            if (count($arguments) > $maximumArguments){
                $this->error("The relation {$passedRelationshipName} expects a maximum of {$maximumArguments} argument(s) not {$argumentsCount}");
                return false;
            }

            $this->relationships[] = [
                'type' => $relationshipName,
                'model' => array_filter($arguments, function ($argument, $index){
                    return $index === 0;
                }, ARRAY_FILTER_USE_BOTH)[0],
                'parameters' => array_filter($arguments, function ($argument, $index){
                    return $index !== 0;
                }, ARRAY_FILTER_USE_BOTH),
            ];
        }

        return true;
    }
}
