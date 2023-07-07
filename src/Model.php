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
    /**
     * 2023-06-12
     * Use this relation when the source model has the foreign key
     * @var string
     */
    public const BELONGS_TO_ONE_RELATION        = 'BELONGS_TO_ONE_RELATION';

    /**
     * 2023-06-12
     * Use this relation when the related model has the foreign key
     * @var string
     */
    public const HAS_MANY_RELATION              = 'HAS_MANY_RELATION';

    /**
     * 2023-06-12
     * Just like HAS_MANY_RELATION but for one related row
     * @var string
     */
    public const HAS_ONE_RELATION                = 'HAS_ONE_RELATION';

    /**
     * 2023-06-12
     * Use this relation when the model is related to a list of other models through a join table
     * @var string
     */
    public const MANY_TO_MANY_RELATION          = 'MANY_TO_MANY_RELATION';

    /**
     * 2023-06-12
     * Use this relation when the model is related to a single model through a join table 
     * @var string
     */
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
     * @param array<string, mixed> $dataGraph
     * @param array<string, Relation> $iRelations
     * @return Model
     */
    public static function create(array $dataGraph, array $iRelations=[]): ?self
    {// 2023-06-14
        $tableIDsMap = static::getTableIDsMap();
        $ids = array_intersect_key($dataGraph, $tableIDsMap);

        foreach($ids as $id) if($id === null) return null;

        $iModel = new static();
        foreach($dataGraph as $columnName=>$columnValue)
        {
            $iRelation = $iRelations[$columnName] ?? null;

            if($iRelation === null)
            {
                $iModel->{$columnName} = $columnValue;
                continue;
            }

            $relatedModelClass = $iRelation->getRelatedModelClass();

            $typeIsArray = in_array($iRelation->getType(), [self::HAS_MANY_RELATION, self::MANY_TO_MANY_RELATION]);

            if(is_array($columnValue))
            {
                $iRelatedModels = [];
                foreach($columnValue as $columnValueItem)
                {
                    $iRelatedModel = $relatedModelClass::create($columnValueItem, $iRelation->getChildRelations());

                    if($iRelatedModel !== null) $iRelatedModels[] = $iRelatedModel;
                }

                if(!$typeIsArray)
                {
                    if(count($iRelatedModels) > 1) throw new \Exception('Relation is not of type array');

                    $iModel->{$iRelation->getName()} = $iRelatedModels[0] ?? null;
                }
                else
                {
                    $iModel->{$iRelation->getName()} = $iRelatedModels;
                }

                continue;
            }

            $iRelatedModel = $relatedModelClass::create($columnValue, $iRelation->getChildRelations());

            if($iRelatedModel !== null) $iModel->{$iRelation->getName()} = $iRelatedModel;
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
     * @return ModelQueryBuilder
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

    public static function relatedQuery(string $relationName): ModelQueryBuilder
    {
        $relationMappings = static::getRelationMappings();

        $relationMapping = $relationMappings[$relationName] ?? null;

        if($relationMapping === null) throw new \Exception('Relation mapping "'.$relationName.'" not found');

        /** @var class-string<Model> $relatedModelClass */
        $relatedModelClass = $relationMapping['modelClass'];

        return $relatedModelClass::query();
    }

    public function _relatedQuery(): void
    {

    }

    public static function startTransaction(?Client $iClient=null): Transaction
    {// 2023-06-12
        
        // NOTE: A transaction should probably just be a wrapper around a client. Or a querybuilder with a transaction object.

        if($iClient === null) $iClient = Objection::getClient();

        $iTransaction = new Transaction($iClient);

        return $iTransaction;
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
