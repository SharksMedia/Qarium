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
    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        $options = $iBuilder->getFindOptions();

        if($options['dontCallFindHooks'] ?? false) return $result;

        return $this->callAfterFind($iBuilder, $result);
    }

    public function callStaticBeforeFind(ModelQueryBuilderOperationSupport $iBuilder)
    {
        $arguments = StaticHookArguments::create($iBuilder);

        /** @var \Model $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::beforeFind($arguments);
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function callAfterFind(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        $options = $iBuilder->getFindOptions();
        $this->callInstanceAfterFind($iBuilder->getContext(), $result, $options['callAfterFindDeeply'] ?? null);

        $results = $this->callStaticAfterFind($iBuilder, $result);

        return $results;
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function callStaticAfterFind(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        $arguments = StaticHookArguments::create($iBuilder, $result);

        /** @var \Model $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::afterFind($arguments) ?? $result;
    }

    /**
     * @param array|Model|null $results
     * @return array|Model|null
     */
    public function callInstanceAfterFind($context, $results, $deep)
    {
        $results = $results ?? [];

        // $firstResult = reset($results);
        $firstResult = &$results[0] ?? null;

        if(is_bool($firstResult)) $firstResult = null;

        if(is_array($results))
        {
            if(count($results) === 1) return $this->callAfterFindForOne($context, $firstResult, $results, $deep);

            return $this->callAfterFindArray($context, $results, $deep);
        }

        return $this->callAfterFindForOne($context, $firstResult, $results, $deep);
    }

    public function callAfterFindArray($context, array &$results, $deep)
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
    public function callAfterFindForOne($context, ?Model &$model, $results, $deep)
    {
        if($deep)
        {
            $this->callAfterFindForRelations($context, $model, $results);
            return $this->doCallAfterFind($context, $model, $results);
        }

        return $this->doCallAfterFind($context, $model, $results);
    }

    public function callAfterFindForRelations($context, ?array &$model, array &$results)
    {
        if($model === null) return false;

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

    public function doCallAfterFind($context, ?Model &$model, array &$result)
    {
        if($model === null) return $result;

        $model->lafterFind($context->userContext);

        return $result;
    }

    public function getAfterFindHook(Model &$model)
    {
        if($model->hasAfterFind()) return [$model, 'lafterFind'];
        if($model->hasAfterGet()) return [$model, 'lafterGet'];

        return null;
    }


}
