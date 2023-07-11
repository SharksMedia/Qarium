<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\ModelQueryBuilder;

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\QueryBuilder;
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
     * @var QueryBuilder
     */
    private static ?QueryBuilder $iQueryBuilder = null;

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

    public static function getRelationUnsafe(string $relationName): Relations\Relation
    {
        $rawRelationsMap = static::getRelationMappings();
        $rawRelation = $rawRelationsMap[$relationName] ?? null;

        if($rawRelation === null) throw new \Exception("Relation $relationName does not exist on model " . static::class);

        static $relationsCache = [];

        if(!isset($relationsCache[$relationName]))
        {
            $relationsCache[$relationName] = Relations\Relation::create($rawRelation, static::class);
        }
    }

    /**
     * 2023-07-10
     * @return array<string, mixed>
     */
    public static function getDefaultFindOptions(): array
    {
        return [];
    }

    public static function getDefaultGraphOptions(): ?array
    {
        return null;
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
    {
    }

    /**
     * 2023-06-12
     * @param Transaction|Client|null $iTransaction
     * @return ModelQueryBuilder
     */
    public static function query($iTransactionOrClient=null): ModelQueryBuilder
    {
        $iBuilder = ModelQueryBuilder::forClass(static::class)
            ->transacting($iTransactionOrClient);

        static::onCreateQuery($iBuilder);

        return $iBuilder;
    }

    public static function relatedQuery(string $relationName): ModelQueryBuilder
    {
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
     * @return void
     */
    public static function onCreateQuery(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * @return void
     */
    public static function beforeFind(): void { /* Do nothing by default. */ }

    /**
     * 2023-06-12
     * @return void
     */
    public static function afterFind(): void { /* Do nothing by default. */ }

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

    public static function getQueryBuilder(?QueryBuilder $iQueryBuilder=null): ?QueryBuilder
    {
        if($iQueryBuilder !== null) static::$iQueryBuilder = $iQueryBuilder;

        return static::$iQueryBuilder;
    }

    public static function getQueryBuilderQuery(): ?QueryBuilder
    {
        return static::getQueryBuilder()->table(static::getTableName());
    }
}
