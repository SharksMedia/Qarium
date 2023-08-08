<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\Objection\StaticHookArguments;

class FindOperation extends ModelQueryBuilderOperation
{
    public function onBefore2(ModelQueryBuilderOperationSupport $builder, ...$arguments): bool
    {
        return $this->callStaticBeforeFind($builder) ?? true;
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelQueryBuilderOperationSupport $builder, &$result)
    {

        $options = $builder->getFindOptions();

        if($options['dontCallFindHooks'] ?? false) return $result;

        return $this->callAfterFind($builder, $result);
    }

    public function callStaticBeforeFind(ModelQueryBuilderOperationSupport $builder)
    {

        $arguments = StaticHookArguments::create($builder);

        return $builder->getModelClass()::beforeFind($arguments);
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function callAfterFind(ModelQueryBuilderOperationSupport $builder, $result)
    {
        $options = $builder->getFindOptions();
        $this->callInstanceAfterFind($builder->getContext(), $result, $options['callAfterFindDeeply'] ?? null);

        return $this->callStaticAfterFind($builder, $result);
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function callStaticAfterFind(ModelQueryBuilderOperationSupport $builder, $result)
    {
        $arguments = StaticHookArguments::create($builder, $result);

        $builder->getModelClass()::afterFind($arguments);

        return $result;
    }

    /**
     * @param array|Model|null $results
     * @return array|Model|null
     */
    public function callInstanceAfterFind($context, $results, $deep)
    {
        $results = $results ?? [];

        $firstResult = reset($results);

        if(is_bool($firstResult)) $firstResult = null;

        if(is_array($firstResult))
        {
            if(count($results) === 1) return $this->callAfterFindForOne($context, $firstResult, $results, $deep);

            return $this->callAfterFindArray($context, $results, $deep);
        }

        return $this->callAfterFindForOne($context, $firstResult, $results, $deep);
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

    /**
     * @return array|Model|null
     */
    public function callAfterFindForOne($context, ?Model $model, $results, $deep)
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
