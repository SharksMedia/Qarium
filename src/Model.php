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
    protected static array $metadataCache = [];

    private static bool $shouldCloneObjectAttributes = true;

    public static string $tableName;
    public static array $idColumn = [];
    public static $concurrency = null;

    /** array<int, string> $omitPropsFromDatabaseArray */
    private array $omitPropsFromDatabaseArray = [];

    /**
     * 2023-06-12
     * @return string
     */
    static function getTableName(): string { return static::$tableName; }
    // abstract static function getTableName(): string;

    /**
     * 2023-06-12
     * @return array<int, string>
     */
    static function getTableIDs(): array { return static::$idColumn; }
    // abstract static function getTableIDs(): array;

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

    public function getOmitPropsFromDatabaseArray(): array
    {
        return $this->omitPropsFromDatabaseArray ?? [];
    }

    public function setOmitPropsFromDatabaseArray(array $props): void
    {
        $this->omitPropsFromDatabaseArray = array_merge($this->omitPropsFromDatabaseArray, $props);
    }

    public static function getTableMetadata(array $options=[]): ?array
    {// 2023-07-31
        $tableName = static::getTableName();

        return static::$metadataCache[$tableName] ?? null;
    }

    private static function getIdRelationProperty(string $modelClass)
    {
        $idColumn = $modelClass::getTableIDs();
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
        $addedProps = $model->isAnonymous()
            ? [$columnName]
            : array_keys((array)$model->createFromDatabaseArray([])); // NOTE: possible to use get_class_vars

        $row = [];
        $row[$columnName] = null;

        $props = $model->isAnonymous()
            ? [$columnName]
            : array_keys((array)$model->createFromDatabaseArray($row));

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

    /**
     * 2023-06-12
     * @return array<int, string>
     */
    public static function getRelationNames(): array
    {
        return array_keys(static::getRelationMappings());
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

    public function isAnonymous(): bool
    {
        return false;
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
            if(!property_exists($iModel, $columnName))
            {
                debug_print_backtrace();
                throw new \Exception("Column \"$columnName\" does not exist on model " . static::class);
            }

            $iModel->{$columnName} = $columnValue;
        }

        return $iModel;
    }

    public static function getModifiers(): array
    {// 2023-08-02
        return [];
    }

    public static function modifierNotFound(ModelQueryBuilder $iBuilder, string $modifierName): void
    {// 2023-08-02
        throw new ModifierNotFoundError($modifierName);
    }

    // Merges and converts `model`'s query properties into array.
    private static function mergeQueryProps(Model $iModel, array $array, array $omitFromArray, ModelQueryBuilder $iBuilder): array
    {
        $array = self::convertExistingQueryProps($array, $iBuilder);
        $array = self::convertAndMergeHiddenQueryProps($iModel, $array, $omitFromArray, $iBuilder);

        return $array;
    }

    // Converts the query properties in `json` to knex raw instances.
    // `json` may have query properties even though we removed them.
    // For example they may have been added in lifecycle hooks.
    private static function convertExistingQueryProps(array $array, ModelQueryBuilder $iBuilder): array
    {
        $keys = array_keys($array);

        for($i = 0, $l=count($keys); $i<$l; ++$i)
        {
            $key = $keys[$i];
            $value = $array[$key];

            if(self::isQueryProp($value))
            {
                $array[$key] = self::queryPropToQueryBuilderRaw($value, $iBuilder);
            }
        }

        return $array;
    }

    private function getPropertyNameToColumnName(string $propName): string
    {
        return $propName;
    }

    protected function getQueryProps(): array
    {
        return [];
    }

    // Converts and merges the query props that were split from the model
    // and stored into QUERY_PROPS_PROPERTY.
    private static function convertAndMergeHiddenQueryProps(Model $iModel, array $array, array $omitFromArray, ModelQueryBuilder $iBuilder)
    {
        $queryProps = $iModel->getQueryProps();

        if(!$queryProps)
        {
            // The model has no query properties.
            return $array;
        }

        foreach($queryProps as $key=>$value)
        {
            if(!$omitFromArray || !in_array($key, $omitFromArray))
            {
                $queryProp = self::queryPropToQueryBuilderRaw($value, $iBuilder);
                $array[$iModel->getPropertyNameToColumnName($key)] = $queryProp;
            }
        }

        return $array;
    }
    
    private static function isQueryProp($value)
    {
        if(!is_object($value)) return false;

        return
            Utilities::isQueryBuilder($value) ||
            Utilities::isQueryBuilderRaw($value) ||
            Utilities::isQueryBuilderRawConvertable($value) ||
            Utilities::isModelQueryBuilderBase($value);
    }

    // Converts a query property into a knex `raw` instance.
    private static function queryPropToQueryBuilderRaw($value, ModelQueryBuilder $iBuilder): \Sharksmedia\QueryBuilder\Statement\Raw
    {
        if(Utilities::isQueryBuilder($value))
        {
            /** @var ModelQueryBuilder $value */
            return $value->subQueryOf($value, $iBuilder);
        }
        else if(Utilities::isQueryBuilderRaw($value))
        {
            return $value;
        }
        else if(Utilities::isQueryBuilderRawConvertable($value))
        {
            throw new \Exception("Not implemented.");
        }
        else if(Utilities::isModelQueryBuilderBase($value))
        {
            throw new \Exception("Not implemented.");
        }
        else
        {
            throw new \Exception("Unknown query prop type.");
        }
    }

    private function formatDatabaseArray(array $array, array $options): array
    {
        // $columnNameMappers = $this->getColumnNameMappers();

        // $array = self::formatArrayAttributes($array, static::class);

        // if($columnNameMappers)
        // {
        //     $array = $columnNameMappers->format($array);
        // }

        return $array;
    }

    public function toDatabaseArray(ModelQueryBuilder $iBuilder): array
    {
        $options =
        [
            'virtuals'=>false,
            'shallow'=>true,
            'omit'=>static::getRelationNames(),
            'pick'=>null,
            'omitFromArray'=>$this->getOmitPropsFromDatabaseArray(),
            'cloneObjects'=>static::$shouldCloneObjectAttributes,
        ];

        $array = self::toDatabaseArrayImplementation($this, $options);
        $array = $this->formatDatabaseArray($array, $options);

        return self::mergeQueryProps($this, $array, $options['omitFromArray'], $iBuilder);
    }

    /**
     * @param Model $iModel
     * @param array|Model $arrayOrModel
     * @param array $options
     */
    function parseRelationsIntoModelInstances(Model $iModel, $arrayOrModel, $options=[])
    {
        static $cache = [];

        // $options['cache']->offsetSet($json, $model);

        foreach($iModel::getRelationNames() as $relationName)
        {
            $relationArray = $cache[$relationName] ?? null;

            if($relationName === null) continue;

            $iRelation = $iModel::getRelationUnsafe($relationName);
            $iRelationModel = self::parseRelation($relationArray, $iRelation, $options);

            if($iRelationModel !== $relationArray) $iModel->{$iRelation->getName()} = $iRelationModel;
        }

        return $iModel;
    }

    function parseRelation(array $array, Relations\Relation $iRelation, array $options)
    {
        $models = [];
        $didChange = false;

        foreach($array as $item)
        {
            $model = self::parseRelationObject($item, $iRelation, $options);

            if($model !== $item) $didChange = true;

            $models[] = $model;
        }

        return $didChange ? $models : $array;
    }

    function parseRelationObject($arrayOrModel, Relations\Relation $iRelation, array $options)
    {
        if(!is_array($arrayOrModel) & !is_object($arrayOrModel)) return $arrayOrModel;

        $modelClass = $iRelation->getRelatedModelClass();

        if($arrayOrModel instanceof $modelClass)
        {
            return parseRelationsIntoModelInstances($arrayOrModel, $arrayOrModel, $options);
        }

        $modelClass::fromArray($arrayOrModel, $options);
    }

    public static function ensureModel($model, $options=[])
    {
        $modelClass = static::class;

        if(!$model) return null;

        if($model instanceof $modelClass)
        {
            return self::parseRelationsIntoModelInstances($model, $model, $options);
        }
        else
        {
            // return $modelClass::createFromArray($model, $options);

            return self::createFromDatabaseArray($model);
        }
    }

    public static function ensureModelArray($models, $options): array
    {
        $iModels = [];

        if(!isset($models[0])) $models = [$models];

        foreach($models as $model) $iModels[] = self::ensureModel($model, $options);

        return $iModels;
    }

    private static function toDatabaseArrayImplementation(Model $iModel, array $options)
    {
        $array = [];

        $metaData = $iModel->getTableMetadata($options) ?? $iModel->fetchTableMetadata();

        $databaseProps = array_column($metaData, 'Field');

        foreach($databaseProps as $index=>$propName)
        {
            self::assignArrayValue($array, $propName, $iModel->{$propName}, $options);
        }

        return $array;
    }

    private static function assignArrayValue(array &$array, string $propName, /* mixed */ $value, array $options): void
    {
        $type = gettype($value);

        $valid =
        (
            $type !== 'object' && // Note: In PHP, function is considered as 'object', so 'function' is removed
            $type !== 'NULL' &&
            !self::isInternalProp($propName) &&
            !self::shouldOmit($propName, $options) &&
            self::shouldPick($propName, $options)
        );

        if($valid)
        {
            $array[$propName] = (is_array($value) || is_object($value))
                ? self::toArray($value, $options)
                : $value;
        }
    }

    private static function isInternalProp(string $propName): bool
    {
        return false;
    }

    private static function shouldOmit(string $propName, array $options): bool
    {
        $shouldOmit =
        (
            (isset($options['omit']) && in_array($propName, $options['omit'])) ||
            (isset($options['omitFromJson']) && in_array($propName, $options['omitFromJson']))
        );

        return $shouldOmit;
    }

    private static function shouldPick(string $propName, array $options): bool
    {
        return !isset($options['pick']) || array_key_exists($propName, $options['pick']);
    }

    private static function toArray($value, array $options): array
    {
        if(is_array($value))
        {
            return $value;
            // return self::toArrayImpl($value, $options);
        }
        else if(is_object($value))
        {
            return self::toDatabaseArray($value, $options);
        }

        return $value;
    }
}
