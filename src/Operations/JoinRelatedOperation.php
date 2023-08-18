<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\RelationExpression;
use Sharksmedia\Qarium\RelationJoiner;

class JoinRelatedOperation extends ModelSharQOperation
{
    /**
     * 2023-07-11
     * @var array
     */
    private array $calls = [];

    public function getJoinOperation(): string
    {
        return $this->options['joinOperation'];
    }

    public function addCall($call): void
    {
        $this->calls[] = $call;
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();
        $joinOperation = $this->getJoinOperation();

        $mergedExpression = RelationExpression::create();

        foreach($this->calls as $call)
        {
            $options = $call['options'] ?? [];

            $expression = RelationExpression::create($call['expression'], $options);
            if($expression->getChildCount() === 1) self::applyAlias($expression, $modelClass, $iBuilder, $options);

            if(isset($options['aliases'])) self::applyAliases($expression, $modelClass, $options);

            $mergedExpression = $mergedExpression->merge($expression);
        }

        $joiner = new RelationJoiner(['modelClass'=>$modelClass]);

        $joiner->setOptions(['joinOperation'=>$joinOperation]);
        $joiner->setExpression($mergedExpression);
        $joiner->setModifiers($iBuilder->getModifiers());
        $joiner->build($iBuilder, false);
    }

    private static function applyAlias(RelationExpression $expression, string $modelClass, ModelSharQ $iBuilder, array $options)
    {
        $children = $expression->getNode()->iChildNodes;

        /** @var \Sharksmedia\Qarium\RelationNode $child */
        $child = array_shift($children);

        /** @var \Model $modelClass */
        $relation = $modelClass::getRelation($child->relationName); 

        $alias = $child->name;

        if(($options['alias'] ?? false) === false) $alias = $iBuilder->getTableRefFor($relation->getRelatedModelClass());
        else if(is_string($options['alias'] ?? false)) $alias = $options['alias'];

        if($child->name !== $alias) self::renameRelationExpressionNode($expression, $child->name, $alias);
    }

    private static function applyAliases(RelationExpression $expression, string $modelClass, array $options)
    {
        $children = $expression->getNode()->iChildNodes;

        foreach($children as $child)
        {
            $relation = $modelClass::getRelation($child->relationName);
            $alias = $options['aliases'][$child->getName()] ?? false;

            if($alias && $child->getName() !== $alias) self::renameRelationExpressionNode($expression, $child->getName(), $alias);

            self::applyAliases($child, $relation->getRelatedModelClass(), $options);
        }
    }

    private function renameRelationExpressionNode($expression, $oldName, $newName)
    {
        $node = $expression->getNode();
        $child = $node->iChildNodes[$oldName];
        unset($node->iChildNodes[$oldName]);
        $child->setName($newName);
        $node->iChildNodes[$newName] = $child;
        $expression->setNode($node);
    }
}


