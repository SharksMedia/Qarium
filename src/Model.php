<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\ModelQueryBuilder;

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Query;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Transaction;

use Sharksmedia\Objection\Exceptions\ModifierNotFoundError;

abstract class Model
{
    public const USE_LIMIT_IN_FIRST = true;

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
     * Use this relation when the model is related to a list of models through a join table
     * @var array<string, array>
     */
    private static array $metadataCache = [];

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

    public static function getIdColumnArray()
    {
        return static::getTableIDs();
    }

    public static function fetchTableMetadata(?Client $iClient=null): array
    {
        $iClient = $iClient ?? Objection::getClient();
        // TODO: Create a function on the compiler to do this

        $tableName = static::getTableName();

        if(isset(self::$metadataCache[$tableName])) return self::$metadataCache[$tableName];

        $method = '';
        $options = [];
        $timeout = 0;
        $cancelOnTimeout = false;
        $bindings = [];
        $UUID = 'describe_' . $tableName;
        $iQuery = new Query($method, $options, $timeout, $cancelOnTimeout, $bindings, $UUID);

        // WARN: This is not safe, becuase we are not escaping the table name
        $iQuery->setSQL('SHOW COLUMNS FROM ' . $tableName);

        if(!$iClient->isInitialized()) $iClient->initializeDriver();

        $statement = $iClient->query($iQuery);

        self::$metadataCache[$tableName] = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return self::$metadataCache[$tableName];
    }

    public static function getTableMetadata(array $options=[]): ?array
    {// 2023-07-31
        $tableName = static::getTableName();

        return self::$metadataCache[$tableName] ?? null;
    }

    private static function getIdRelationProperty(string $modelClass)
    {
        $idColumn = $modelClass::getIdColumn();
        if(!is_array($idColumn)) $idColumn = [$idColumn];

        $idColumns = array_map(function($column)
        {
            return $modelClass::getTableName() . '.' . $column;
        }, $idColumn);

        return new RelationProperty($idColumns, function(){ return $modelClass; });
    }

    public static function columnNameToPropertyName(string $columnName)
    {
        static $cache = [];

        $propertyName = $cache[$columnName] ?? null;

        if($propertyName !== null) return $propertyName;

        $model = new static();
        $addedProps = array_keys((array)$model->createFromDatabaseArray([]));

        $row = [];
        $row[$columnName] = null;

        $props = array_keys((array)$model->createFromDatabaseArray($row));
        $propertyName = array_diff($props, $addedProps)[0] ?? null;

        $cache[$columnName] = $propertyName ?? $columnName;

        return $cache[$columnName];
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

        /** @var array<string, Relations\Relation> */
        static $relationsCache = [];

        if(!isset($relationsCache[$relationName]))
        {
            $relationsCache[$relationName] = Relations\Relation::create($relationName, $rawRelation, static::class);
            $relationsCache[$relationName]->setMapping($rawRelation);
        }

        return $relationsCache[$relationName];
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
    public static function afterFind(StaticHookArguments $arguments): void { /* Do nothing by default. */ }

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

    public static function getJoinTableAlias(string $relationPath): string
    {
        return $relationPath.'_join';
    }

    public static function setQueryBuilder(QueryBuilder $iQueryBuilder): void
    {
        static::$iQueryBuilder = $iQueryBuilder;
    }

    public static function getQueryBuilder(): ?QueryBuilder
    {
        return static::$iQueryBuilder;
    }

    public static function getQueryBuilderQuery(): ?QueryBuilder
    {
        return static::getQueryBuilder()->table(static::getTableName());
    }

    /**
     * 2023-06-12
     * @param array<string, mixed> $result
     * @return Model
     */
    public static function createFromDatabaseArray(array $result): static
    {
        $iModel = new static();

        foreach($result as $columnName=>$columnValue)
        {
            if(!property_exists($iModel, $columnName)) throw new \Exception("Column $columnName does not exist on model " . static::class);

            $iModel->{$columnName} = $columnValue;
        }

        return $iModel;
    }

    public static function getModifiers(): array
    {// 2023-08-02
        return [];
    }

    public static function modifierNotFound(ModelQueryBuilder $iBuilder, \Cloure $modifier): void
    {// 2023-08-02
        throw new ModifierNotFoundError($modifier);
    }
}
