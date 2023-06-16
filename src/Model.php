<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\ModelQueryBuilder;

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Transaction;

abstract class Model
{
    public const BELONGS_TO_ONE_RELATION        = 'BELONGS_TO_ONE_RELATION';
    public const HAS_MANY_RELATION              = 'HAS_MANY_RELATION';
    public const AS_ONE_RELATION                = 'AS_ONE_RELATION';
    public const MANY_TO_MANY_RELATION          = 'MANY_TO_MANY_RELATION';
    public const HAS_ONE_THROUGH_RELATION       = 'HAS_ONE_THROUGH_RELATION';

    /**
     * 2023-06-12
     * @return string
     */
    abstract static function getTableName(): string;

    /**
     * 2023-06-12
     * @return array<int, string>
     */
    abstract static function getTableIDs(): array;

    /**
     * 2023-06-12
     * @return array<string, string>
     */
    public static function getTableIDsMap(): array
    {
        return array_combine(static::getTableIDs(), static::getTableIDs());
    }

    /**
     * 2023-06-12
     * @return array<string, array>
     */
    public static function getRelationMappings(): array
    {// 2023-06-12
        return [];
    }
    //
    // public function __construct(array $data)
    // {
    //     foreach($data as $columnName=>$columnValue)
    //     {
    //         $this->{$columnName} = $columnValue;
    //     }
    // }

    /**
     * 2023-06-12
     * @param array<string, mixed> $data
     * @return Model
     */
    public static function create(array $dataGraph, array $relations=[]): ?self
    {// 2023-06-14
        $tableIDsMap = static::getTableIDsMap();
        $ids = array_intersect_key($dataGraph, $tableIDsMap);

        foreach($ids as $id) if($id === null) return null;

        $iModel = new static();
        foreach($dataGraph as $columnName=>$columnValue)
        {
            $relation = $relations[$columnName] ?? null;

            if($relation === null)
            {
                $iModel->{$columnName} = $columnValue;
                continue;
            }

            if(is_array($columnValue))
            {
                $iModel->{$relation->name} = [];
                foreach($columnValue as $columnValueItem)
                {
                    $iRelatedModel = $relation->modelClass::create($columnValueItem, $relations);

                    if($iRelatedModel !== null) $iModel->{$relation->name}[] = $iRelatedModel;
                }

                continue;
            }

            $iRelatedModel = $relation->modelClass::create($columnValue, $relations);

            if($iRelatedModel !== null) $iModel->{$relation->name}[] = $iRelatedModel;
        }

        return $iModel;

        //
        // $iModel = new static();
        //
        // foreach($data as $columnName=>$columnValue)
        // {
        //     $iModel->{$columnName} = $columnValue;
        // }
        //
        // return $iModel;
    }

    /**
     * 2023-06-12
     * @param Transaction|Client|null $iTransaction
     * @return QueryBuilder
     */
    public static function query($iTransactionOrClient=null): ModelQueryBuilder
    {// 2023-06-12
        // NOTE: We have to extends the QueryBuilder so we can override the run/exec command and return a Model.
        if($iTransactionOrClient instanceof Transaction)
        {
            $iTransaction = $iTransactionOrClient;
            $iClient = $iTransaction->getClient();
        }
        else if($iTransactionOrClient instanceof Client)
        {
            $iClient = $iTransactionOrClient;
            $iTransaction = null;
        }
        else
        {
            $iClient = Objection::getClient();
            $iTransaction = null;
        }

        $iQueryBuilder = new ModelQueryBuilder(static::class , $iClient, '');

        $iQueryBuilder
            ->transaction($iTransaction)
            ->table(static::getTableName());

        return $iQueryBuilder;
    }

    public function relatedQuery(): ModelQueryBuilder
    {

    }

    public static function startTransaction(): Transaction
    {// 2023-06-12

    }



    /**
     * 2023-06-12
     * Runs before the insert query is executed.
     * @return void
     */
    public static function beforeInsert(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * Runs after the insert query is executed.
     * @return void
     */
    public static function afterInsert(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * Runs before the update query is executed.
     * @return void
     */
    public static function beforeUpdate(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * Runs after the update query is executed.
     * @return void
     */
    public static function afterUpdate(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * Runs before the delete query is executed.
     * @return void
     */
    public static function beforeDelete(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * Runs after the delete query is executed.
     * @return void
     */
    public static function afterDelete(): void { /* Do nothing by default. */ }
}
