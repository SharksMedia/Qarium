<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\ModelQueryBuilderOperation;

// 2023-07-11

class StaticHookArguments
{
    /**
     * @var ModelQueryBuilderContextUser|null
     */
    private ModelQueryBuilder $iBuilder;

    /**
     * @var array
     */
    private ?array $result;

    public function __construct(ModelQueryBuilder $iBuilder, ?array $result=null)
    {
        $this->iBuilder = $iBuilder;
        $this->result = $result;
    }

    public static function create(ModelQueryBuilder $iBuilder, ?array $result=null): StaticHookArguments
    {
        return new StaticHookArguments($iBuilder, $result);
    }

    public function getAsFindQuery(): \Closure
    {
        return function()
        {
            return $this->iBuilder
                ->toFindQuery()
                ->clearWithGraphFetched();
        };
    }

    public function getContext()
    {
        return $this->iBuilder->getContext();
    }

    public function getTransaction()
    {
        return $this->iBuilder->getUnsafeQueryBuilder();
    }

    public function getRelation()
    {
        $operation = $this->iBuilder->findOperation(function($operation){ return self::_hasRelation($operation); });

        if($operation === null) return null;

        return self::_getRelation($operation);
    }

    public function getModelOptions()
    {
        $operation = $this->iBuilder->findOperation(function($operation){ return self::_hasModelOptions($operation); });

        if($operation === null) return null;

        return self::_getModelOptions($operation);
    }

    public function getItems()
    {
        $operation = $this->iBuilder->findOperation(function($operation){ return self::_hasItems($operation); });

        if($operation === null) return null;

        return self::_getItems($operation);
    }

    public function getInputItems()
    {
        $operation = $this->iBuilder->findOperation(function($operation){ return self::_hasInputItems($operation); });

        if($operation === null) return null;

        return self::_getInputItems($operation);
    }

    private static function _getRelation($operation)
    {
        return $operation->getRelation();
    }

    private static function _hasRelation($operation)
    {
        return self::_getRelation($operation) !== null;
    }

    private static function _getModelOptions($operation)
    {
        return $operation->getModelOptions();
    }

    private static function _hasModelOptions($operation)
    {
        return self::_getModelOptions($operation) !== null;
    }

    private static function _getItems($operation)
    {
        return $operation->getInstance() ?? $operation->parentOperation;
    }

    private static function _hasItems($operation)
    {
        return self::_getItems($operation) !== null;
    }

    private static function _getInputItems($operation)
    {
        return $operation->getInputItems();
    }

    private static function _hasInputItems($operation)
    {
        return self::_getInputItems($operation) !== null;
    }







}