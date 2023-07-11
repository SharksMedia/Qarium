<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\StaticHookArguments;

class FindOperation extends ModelQueryBuilderOperation
{
    public function onBefore2(ModelQueryBuilder $builder, $arguments): bool
    {
        return $this->callStaticBeforeFind($builder) ?? true;
    }

    public function onAfter3(ModelQueryBuilder $builder, array $result): array
    {
        $options = $builder->findOptions();

        if($options['dontCallFindHooks']) return $result;

        return $this->callAfterFind($builder, $result);
    }

    public function callStaticBeforeFind(ModelQueryBuilder $builder)
    {
        $arguments = StaticHookArguments::create($builder);

        return $builder->getModelClass()::beforeFind($arguments);
    }

    public function callAfterFind(ModelQueryBuilder $builder, array $result): array
    {
        $options = $builder->findOptions();
        $this->callInstanceAfterFind($builder->context(), $result, $options['callAfterFindDeeply']);

        return $this->callStaticAfterFind($builder, $result);
    }

    public function callStaticAfterFind(ModelQueryBuilder $builder, array $result): array
    {
        $arguments = StaticHookArguments::create($builder, $result);

        return $builder->getModelClass()::afterFind($arguments);
    }

    public function callInstanceAfterFind(string $context, array $results, $deep)
    {
        if(is_array($results[0] ?? null))
        {
            if(count($results) === 1) return $this->callAfterFindForOne($context, $results[0], $results, $deep);

            return $this->callAfterFindArray($context, $results, $deep);
        }

        return $this->callAfterFindForOne($context, $results, $results, $deep);
    }

    public function callAfterFindArray($context, array $results, $deep)
    {
        if(count($results) === 0) return $results;

        $mapped = [];
        foreach($results as $result)
        {
            $mapped[] = $this->callAfterFindForOne($context, $result, $results, $deep);
        }

        return $mapped;
    }

    public function callAfterFindForOne($context, $model, array $results, $deep)
    {
        if(!is_array($model)) return $model;

        if($deep)
        {
            $this->callAfterFindForRelations($context, $model, $results);
            return $this->doCallAfterFind($context, $model, $results);
        }

        return $this->doCallAfterFind($context, $model, $results);
    }

    public function callAfterFindForRelations($context, array $model, array $results)
    {
        $results = [];
        foreach($model as $key=>$value)
        {
            if($this->isRelation($value))

            $result = $this->callInstanceAfterFind($context, $value, true);

            $results[] = $result;
        }

        return false;
    }

    public function isRelation($value): bool
    {
        return is_object($value) && $value instanceof Model;
    }

    public function doCallAfterFind($context, Model $model, array $result)
    {
        $afterFindHook = $this->getAfterFindHook($model);

        if($afterFindHook === null) return $result;

        return $afterFindHook($model);
    }

    public function getAfterFindHook(Model $model)
    {
        if($model->hasAfterFind)  return $model->afterFind;
        if($model->hasAfterGet)  return $model->afterGet;

        return null;
    }


}
