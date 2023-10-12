<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\ModelSharQ;

use Sharksmedia\Qarium\Relations\RelationProperty;
use Sharksmedia\SharQ\Client;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Raw;
use Sharksmedia\SharQ\Transaction;

use Sharksmedia\Qarium\Relations\Relation;
use Sharksmedia\Qarium\Relations\RelationOwner;

use Sharksmedia\Qarium\Operations\InstanceFindOperation;
use Sharksmedia\Qarium\Operations\InstanceInsertOperation;
use Sharksmedia\Qarium\Operations\InstanceUpdateOperation;
use Sharksmedia\Qarium\Operations\InstanceDeleteOperation;

use Sharksmedia\Qarium\Exceptions\ModifierNotFoundError;

abstract class Model
{
    public const USE_LIMIT_IN_FIRST = false;

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

    /** @var array<string, Relations\Relation> */
    protected static array $iRelationCache = [];

    private static bool $shouldCloneObjectAttributes = true;

    public static string $tableName;
    public static array $idColumn = [];
    public static $concurrency    = null;

    public static function getRelatedFindQueryMutates(): bool
    {
        return false;
    }

    /**
     * 2023-06-12
     * @return string
     */
    public static function getTableName(): string
    {
        return static::$tableName;
    }
    // abstract static function getTableName(): string;

    /**
     * 2023-06-12
     * @return array<int, string>
     */
    public static function getTableIDs(): array
    {
        return static::$idColumn;
    }
    // abstract static function getTableIDs(): array;
    
    public function lhasIDs(): bool
    {
        foreach (static::getTableIDs() as $idColumn)
        {
            if (!isset($this->$idColumn))
            {
                return false;
            }
        }

        return true;
    }

    public function lset(object $iModel): static
    {
        $props = get_object_vars($iModel);

        foreach ($props as $prop => $value)
        {
            $this->$prop = $value;
        }

        return $this;
    }

    private function hasCompositeId(): bool
    {
        return count(static::getTableIDs()) > 1;
    }

    public function getID()
    {
        if (!$this->lhasIDs())
        {
            $idColumn = $this->getTableIDs()[0];

            return $this->$idColumn;
        }

        $ids = [];

        foreach ($this->getTableIDs() as $idColumn)
        {
            $ids[] = $this->$idColumn;
        }

        return $ids;
    }

    /**
     * 2023-06-12
     * @var SharQ
     */
    private static ?SharQ $iSharQ = null;

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

    public static function fetchTableMetadata(?Client $iClient = null, ?string $schema = null): array
    {
        $iClient = $iClient ?? Qarium::getClient();
        // TODO: Create a function on the compiler to do this

        $tableName = static::getTableName();

        // if(isset(static::$metadataCache[$tableName])) return static::$metadataCache[$tableName];

        $iQB = (new SharQ($iClient, $schema))
            ->select('*')
            ->from('information_schema.columns')
            ->where('table_name', '=', $tableName)
            ->andWhere('table_catalog', '=', new Raw('DATABASE()'));

        if ($schema !== null)
        {
            $iQB->andWhere('table_schema', '=', $schema);
        }

        $iQuery = $iQB->toQuery();

        if (!$iClient->isInitialized())
        {
            $iClient->initializeDriver();
        }

        $statement = $iClient->query($iQuery);

        // static::$metadataCache[$tableName] = $statement->fetchAll(\PDO::FETCH_ASSOC);
        //
        // return static::$metadataCache[$tableName];

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getOmitPropsFromDatabaseArray(): array
    {
        return [];
    }

    public static function getTableMetadata(array $options = []): ?array
    {// 2023-07-31
        $tableName = static::getTableName();

        return static::$metadataCache[$tableName] ?? null;
    }

    public static function getIdRelationProperty()
    {
        $idColumn = static::getTableIDs();

        if (!is_array($idColumn))
        {
            $idColumn = [$idColumn];
        }

        $idColumns = array_map(function($column)
        {
            return static::getTableName().'.'.$column;
        }, $idColumn);

        return new RelationProperty($idColumns, function()
        { return static::class; });
    }

    public static function getColumnsNames(): array
    {
        $metadata = static::getTableMetadata() ?? static::fetchTableMetadata();

        return array_map(function($column)
        {
            return $column['Field'];
        }, $metadata);
    }

    public static function columnNameToPropertyName(string $columnName)
    {
        // static $cache = [];
        //
        // $propertyName = $cache[$columnName] ?? null;
        //
        // if($propertyName !== null) return $propertyName;

        $model      = new static();
        $addedProps = $model->isAnonymous()
            ? [$columnName]
            : static::getColumnsNames();

        $row              = [];
        $row[$columnName] = null;

        $props = $model->isAnonymous()
            ? [$columnName]
            : static::getColumnsNames();

        $propertyName = array_diff($props, $addedProps)[0] ?? null;

        return $propertyName ?? $columnName;

        // $cache[$columnName] = $propertyName ?? $columnName;
        //
        // return $cache[$columnName];
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

    /**
     * 2023-06-12
     * @param string $relationName
     * @return array<string, mixed>|null
     */
    public static function getRelationUnsafe(string $relationName): ?array
    {
        $rawRelationsMap = static::getRelationMappings();

        $rawRelation = $rawRelationsMap[$relationName] ?? null;

        return $rawRelation;
    }

    public static function getRelation(string $relationName): Relations\Relation
    {
        $rawRelation = static::getRelationUnsafe($relationName);

        if ($rawRelation === null)
        {
            throw new RelationDoesNotExistError(static::class, $relationName);
        }
        // get unsafe relation checks for errors

        $iRelation = Relations\Relation::create($relationName, $rawRelation, static::class);

        $iRelation->setMapping($rawRelation);

        return $iRelation;
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
    public static function create(array $dataGraph, array $iRelations = []): ?self
    {
    }

    /**
     * 2023-06-12
     * @param self $instance
     * @param Client|Transaction|null $iTransactionOrClient
     * @return ModelSharQ
     */
    private static function instanceQuery(self $instance, $iTransactionOrClient = null): ModelSharQ
    {
        /** @var \Model $modelClass */
        $modelClass = $instance::class;

        return $modelClass::query($iTransactionOrClient)
            ->findOperationFactory(function() use ($instance)
            {
                return new InstanceFindOperation('find', ['instance' => $instance]);
            })
            ->insertOperationFactory(function() use ($instance)
            {
                return new InstanceInsertOperation('insert', ['instance' => $instance]);
            })
            ->updateOperationFactory(function() use ($instance)
            {
                return new InstanceUpdateOperation('update', ['instance' => $instance]);
            })
            ->patchOperationFactory(function() use ($instance)
            {
                return new InstanceUpdateOperation('patch', ['instance' => $instance, modelOptions => ['patch' => true]]);
            })
            ->deleteOperationFactory(function() use ($instance)
            {
                return new InstanceDeleteOperation('delete', ['instance' => $instance]);
            })
            ->relateOperationFactory(function() use ($instance)
            {
                throw new \Exception("relate makes no sense in this context");
            })
            ->unrelateOperationFactory(function() use ($instance)
            {
                throw new \Exception("unrelate makes no sense in this context");
            });
    }

    /**
     * 2023-06-12
     * @param Transaction|Client|null $iTransaction
     * @return ModelSharQ
     */
    public static function query($iTransactionOrClient = null): ModelSharQ
    {
        $query = ModelSharQ::forClass(static::class)
            ->transacting($iTransactionOrClient);

        static::onCreateQuery($query);

        return $query;
    }

    public function lquery($iTransactionOrClient = null): ModelSharQ
    {
        return self::instanceQuery($this, $iTransactionOrClient);
    }

    public static function relatedQuery(string $relationsName, ?Transaction $iTransaction = null): ModelSharQ
    {
        return static::__relatedQuery(static::class, $relationsName, $iTransaction);
    }

    /**
     * 2023-06-12
     * @param class-string<Model> $modelClass
     * @param string $relationName
     * @param Transaction|Client|null $iTransaction
     * @param bool $alwaysReturnArray
     * @return ModelSharQ
     */
    private static function __relatedQuery(string $modelClass, string $relationName, ?Transaction $iTransaction, bool $alwaysReturnArray = false): ModelSharQ
    {
        /** @var Relation $iRelation */
        $iRelation = call_user_func([$modelClass, 'getRelation'], $relationName);

        /** @var Model $relatedModelClass */
        $relatedModelClass = $iRelation->getRelatedModelClass();

        /** @var ModelSharQ $query */
        $query = call_user_func([$relatedModelClass, 'query']);

        return $query->findOperationFactory(function($iBuilder) use ($iRelation, $alwaysReturnArray, $modelClass)
        {
            $isSubquery = $iBuilder->for() === null;

            $iRelationOwner = $isSubquery
                ? RelationOwner::createParentReference($iBuilder, $iRelation)
                : RelationOwner::create($iBuilder->for());

            $iOperation = $iRelation->find($iBuilder, $iRelationOwner);

            $iOperation->setAssignResultToOwner($modelClass::getRelatedFindQueryMutates());
            $iOperation->setAlwaysReturnArray($alwaysReturnArray);
            $iOperation->setAlias($isSubquery ? $iRelation->getName() : null);

            return $iOperation;
        })
            ->insertOperationFactory(function($iBuilder) use ($iRelation, $modelClass)
            {
                $iRelationOwner = RelationOwner::create($iBuilder->for());
                $iOperation     = $iRelation->insert($iBuilder, $iRelationOwner);

                $iOperation->assignResultToOwner = call_user_func([$modelClass, 'getRelatedInsertQueryMutates']);

                return $iOperation;
            })
            ->updateOperationFactory(function($iBuilder) use ($iRelation)
            {
                $iRelationOwner = RelationOwner::create($iBuilder->for());

                return $iRelation->update($iBuilder, $iRelationOwner);
            })
            ->patchOperationFactory(function($iBuilder) use ($iRelation)
            {
                $iRelationOwner = RelationOwner::create($iBuilder->for());

                return $iRelation->patch($iBuilder, $iRelationOwner);
            })
            ->deleteOperationFactory(function($iBuilder) use ($iRelation)
            {
                $iRelationOwner = RelationOwner::create($iBuilder->for());

                return $iRelation->delete($iBuilder, $iRelationOwner);
            })
            ->relateOperationFactory(function($iBuilder) use ($iRelation)
            {
                /** @var ModelSharQ $iBuilder */
                /** @var Relations\HasMany|Relations\ManyToMany $iRelation */

                $iRelationOwner = RelationOwner::create($iBuilder->for());

                return $iRelation->relate($iBuilder, $iRelationOwner);
            })
            ->unrelateOperationFactory(function($iBuilder) use ($iRelation)
            {
                /** @var ModelSharQ $iBuilder */
                /** @var Relations\HasMany|Relations\ManyToMany $iRelation */

                $iRelationOwner = RelationOwner::create($iBuilder->for());

                return $iRelation->unrelate($iBuilder, $iRelationOwner);
            });
    }

    public function lrelatedQuery(string $relationsName, $iTransactionOrClient = null): ModelSharQ
    {
        $iBuilder = static::__relatedQuery(static::class, $relationsName, $iTransactionOrClient, false)
            ->for($this);

        return $iBuilder;
    }

    public function isAnonymous(): bool
    {
        return false;
    }

    public static function startTransaction(?Client $iClient = null): Transaction
    {// 2023-06-12
        // NOTE: A transaction should probably just be a wrapper around a client. Or a querybuilder with a transaction object.

        if ($iClient === null)
        {
            $iClient = Qarium::getClient();
        }

        $iTransaction = new Transaction($iClient);

        return $iTransaction;
    }


    /**
     * 2023-06-12
     * @return void
     */
    public static function onCreateQuery(): void
    { /* Do nothing by default. */
    }

    public function lbeforeFind($context): void
    { /* Do nothing by default. */
    }
    public function lafterFind($context)
    {
        return $context;
    }
    public function lbeforeInsert($context): void
    { /* Do nothing by default. */
    }
    public function lafterInsert($arguments)
    {
        return $arguments;
    }
    public function lbeforeUpdate($context): void
    { /* Do nothing by default. */
    }
    public function lafterUpdate($arguments)
    {
        return $arguments;
    }
    public function lbeforePatch($context): void
    { /* Do nothing by default. */
    }
    public function lafterPatch($arguments)
    {
        return $arguments;
    }
    public function lbeforeDelete($context): void
    { /* Do nothing by default. */
    }
    public function lafterDelete($arguments)
    {
        return $arguments;
    }

    /**
     * 2023-06-12
     * @return void
     */
    public static function beforeFind(): void
    { /* Do nothing by default. */
    }

    /**
     * 2023-06-12
     * @return void
     */
    public static function afterFind($arguments)
    {
        return null;
    }

    /**
     * 2023-06-12
     * Runs before the insert query is executed.
     * @return void
     */
    public static function beforeInsert(): void
    { /* Do nothing by default. */
    }

    /**
     * 2023-06-12
     * Runs after the insert query is executed.
     * @return void
     */
    public static function afterInsert($arguments)
    {
        return $arguments;
    }

    /**
     * 2023-06-12
     * Runs before the update query is executed.
     * @return void
     */
    public static function beforeUpdate(): void
    { /* Do nothing by default. */
    }

    /**
     * 2023-06-12
     * Runs after the update query is executed.
     * @return void
     */
    public static function afterUpdate(): void
    { /* Do nothing by default. */
    }

    /**
     * 2023-06-12
     * Runs before the delete query is executed.
     * @return void
     */
    public static function beforeDelete(): void
    { /* Do nothing by default. */
    }

    /**
     * 2023-06-12
     * Runs after the delete query is executed.
     * @return void
     */
    public static function afterDelete(): void
    { /* Do nothing by default. */
    }

    public static function getJoinTableAlias(string $relationPath): string
    {
        return $relationPath.'_join';
    }

    public static function setSharQ(SharQ $iSharQ): void
    {
        static::$iSharQ = $iSharQ;
    }

    public static function getSharQ(): ?SharQ
    {
        $iSharQ = static::$iSharQ ?? null;

        if ($iSharQ === null)
        {
            $iClient = Qarium::getClient();

            if ($iClient === null)
            {
                return null;
            }

            $iSharQ = new SharQ($iClient);
        }

        return $iSharQ;
    }

    public static function getSharQQuery(): ?SharQ
    {
        return static::getSharQ()->table(static::getTableName());
    }

    /**
     * 2023-06-12
     * @param array<string, mixed> $result
     * @return Model
     */
    public static function createFromDatabaseArray(array $result): static
    {
        $iModel = new static();

        foreach ($result as $columnName => $columnValue)
        {
            if (!is_string($columnName) || !property_exists($iModel, $columnName))
            {
                throw new \Exception("Column \"$columnName\" does not exist on model ".static::class);
            }

            // 2023-08-15 hack
            if ($columnValue instanceof Raw)
            {
                continue;
            }

            $iModel->{$columnName} = $columnValue;
        }

        return $iModel;
    }

    public static function getModifiers(): array
    {// 2023-08-02
        return [];
    }

    public static function modifierNotFound(ModelSharQ $iBuilder, string $modifierName): void
    {// 2023-08-02
        throw new ModifierNotFoundError($modifierName);
    }

    // Merges and converts `model`'s query properties into array.
    private static function mergeQueryProps(Model $iModel, array $array, array $omitFromArray, ModelSharQ $iBuilder): array
    {
        $array = self::convertExistingQueryProps($array, $iBuilder);
        $array = self::convertAndMergeHiddenQueryProps($iModel, $array, $omitFromArray, $iBuilder);

        return $array;
    }

    // Converts the query properties in `json` to knex raw instances.
    // `json` may have query properties even though we removed them.
    // For example they may have been added in lifecycle hooks.
    private static function convertExistingQueryProps(array $array, ModelSharQ $iBuilder): array
    {
        $keys = array_keys($array);

        for ($i = 0, $l = count($keys); $i < $l; ++$i)
        {
            $key   = $keys[$i];
            $value = $array[$key];

            if (self::isQueryProp($value))
            {
                $array[$key] = self::queryPropToSharQRaw($value, $iBuilder);
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
    private static function convertAndMergeHiddenQueryProps(Model $iModel, array $array, array $omitFromArray, ModelSharQ $iBuilder)
    {
        $queryProps = $iModel->getQueryProps();

        if (!$queryProps)
        {
            // The model has no query properties.
            return $array;
        }

        foreach ($queryProps as $key => $value)
        {
            if (!$omitFromArray || !in_array($key, $omitFromArray))
            {
                $queryProp                                         = self::queryPropToSharQRaw($value, $iBuilder);
                $array[$iModel->getPropertyNameToColumnName($key)] = $queryProp;
            }
        }

        return $array;
    }
    
    private static function isQueryProp($value)
    {
        if (!is_object($value))
        {
            return false;
        }

        return
            Utilities::isSharQ($value)               ||
            Utilities::isSharQRaw($value)            ||
            Utilities::isSharQRawConvertable($value) ||
            Utilities::isModelSharQBase($value);
    }

    // Converts a query property into a knex `raw` instance.
    private static function queryPropToSharQRaw($value, ModelSharQ $iBuilder): \Sharksmedia\SharQ\Statement\Raw
    {
        if (Utilities::isSharQ($value))
        {
            /** @var ModelSharQ $value */
            return $value->subQueryOf($value, $iBuilder);
        }
        else if (Utilities::isSharQRaw($value))
        {
            return $value;
        }
        else if (Utilities::isSharQRawConvertable($value))
        {
            throw new \Exception("Not implemented.");
        }
        else if (Utilities::isModelSharQBase($value))
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

    public function toDatabaseArray(ModelSharQ $iBuilder): array
    {
        $options =
        [
            'virtuals'      => false,
            'shallow'       => true,
            'omit'          => static::getRelationNames(),
            'pick'          => null,
            'omitFromArray' => self::getOmitPropsFromDatabaseArray(),
            'cloneObjects'  => static::$shouldCloneObjectAttributes,
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
    public static function parseRelationsIntoModelInstances(Model $iModel, $arrayOrModel, $options = [])
    {
        static $cache = [];

        // $options['cache']->offsetSet($json, $model);

        foreach ($iModel::getRelationNames() as $relationName)
        {
            $relationArray = $cache[$relationName] ?? null;

            if ($relationName === null)
            {
                continue;
            }

            $iRelation      = $iModel::getRelationUnsafe($relationName);
            $iRelationModel = self::parseRelation($relationArray, $iRelation, $options);

            if ($iRelationModel !== $relationArray)
            {
                $iModel->{$iRelation->getName()} = $iRelationModel;
            }
        }

        return $iModel;
    }

    public function parseRelation(array $array, Relations\Relation $iRelation, array $options)
    {
        $models    = [];
        $didChange = false;

        foreach ($array as $item)
        {
            $model = self::parseRelationObject($item, $iRelation, $options);

            if ($model !== $item)
            {
                $didChange = true;
            }

            $models[] = $model;
        }

        return $didChange ? $models : $array;
    }

    public function parseRelationObject($arrayOrModel, Relations\Relation $iRelation, array $options)
    {
        if (!is_array($arrayOrModel) & !is_object($arrayOrModel))
        {
            return $arrayOrModel;
        }

        $modelClass = $iRelation->getRelatedModelClass();

        if ($arrayOrModel instanceof $modelClass)
        {
            return self::parseRelationsIntoModelInstances($arrayOrModel, $arrayOrModel, $options);
        }

        $modelClass::fromArray($arrayOrModel, $options);
    }

    public static function ensureModel($model, $options = [])
    {
        $modelClass = static::class;

        if (!$model)
        {
            return null;
        }

        if ($model instanceof $modelClass)
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

        if (!is_array($models) || !isset($models[0]))
        {
            $models = [$models];
        }

        foreach ($models as $model)
        {
            $iModel = self::ensureModel($model, $options);
                
            if (!$iModel)
            {
                continue;
            }

            $iModels[] = $iModel;
        }

        return $iModels;
    }

    private static function toDatabaseArrayImplementation(Model $iModel, array $options)
    {
        $array = [];

        $metaData = $iModel->getTableMetadata($options) ?? $iModel->fetchTableMetadata();

        $databaseProps = array_column($metaData, 'Field');

        foreach ($databaseProps as $index => $propName)
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
            $type !== 'object'                     && // Note: In PHP, function is considered as 'object', so 'function' is removed
            $type !== 'NULL'                       &&
            !self::isInternalProp($propName)       &&
            !self::shouldOmit($propName, $options) &&
            self::shouldPick($propName, $options)
        );

        if ($valid)
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
        if (is_array($value))
        {
            return $value;
            // return self::toArrayImpl($value, $options);
        }
        else if (is_object($value))
        {
            return self::toDatabaseArray($value, $options);
        }

        return $value;
    }

    public static function omitImpl(object &$model, string $prop): void
    {
        unset($model->{$prop});
    }

    public static function propertyNameToColumnName($propertyName)
    {
        // Not supported yet
        return $propertyName;
    }
}
