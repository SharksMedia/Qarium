<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Closure;
use Sharksmedia\Qarium\Operations\RunBeforeOperation;
use Sharksmedia\Qarium\Operations\RunAfterOperation;

use Sharksmedia\Qarium\Operations\OnBuildOperation;
use Sharksmedia\Qarium\Operations\OnBuildSharQOperation;

use Sharksmedia\Qarium\Operations\ModelSharQOperation;
use Sharksmedia\Qarium\Operations\InsertOperation;
use Sharksmedia\Qarium\Operations\InsertAndFetchOperation;
use Sharksmedia\Qarium\Operations\InsertGraphOperation;
use Sharksmedia\Qarium\Operations\InsertGraphAndFetchOperation;

use Sharksmedia\Qarium\Operations\UpdateOperation;
use Sharksmedia\Qarium\Operations\UpdateAndFetchOperation;

use Sharksmedia\Qarium\Operations\UpsertGraphOperation;
use Sharksmedia\Qarium\Operations\UpsertGraphAndFetchOperation;

use Sharksmedia\Qarium\Operations\RangeOperation;
use Sharksmedia\Qarium\Operations\FirstOperation;

use Sharksmedia\Qarium\Operations\DeleteOperation;

use Sharksmedia\Qarium\Operations\SelectOperation;
use Sharksmedia\Qarium\Operations\Selection;
use Sharksmedia\Qarium\Operations\FindOperation;
use Sharksmedia\Qarium\Operations\FindByIdOperation;
use Sharksmedia\Qarium\Operations\FindByIdsOperation;

use Sharksmedia\Qarium\Operations\RelateOperation;
use Sharksmedia\Qarium\Operations\UnrelateOperation;
use Sharksmedia\Qarium\Operations\EagerOperation;

use Sharksmedia\Qarium\Operations\JoinEagerOperation;
use Sharksmedia\Qarium\Operations\JoinRelatedOperation;

use Sharksmedia\Qarium\Operations\NaiveEagerOperation;
use Sharksmedia\Qarium\Operations\FromOperation;
use Sharksmedia\Qarium\Operations\OnErrorOperation;
use Sharksmedia\Qarium\Operations\SharQOperation;

use Sharksmedia\Qarium\Operations\ResultSizeOperation;

use Sharksmedia\Qarium\Operations\WhereInEagerOperation;
use Sharksmedia\Qarium\Relations\RelationOwner;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Raw;

class ModelSharQ extends ModelSharQBase
{
    public const JOIN_EAGER_ALGORITHM     = 'JOIN_EAGER_ALGORITHM';
    public const NAIVE_EAGER_ALGORITHM    = 'NAIVE_EAGER_ALGORITHM';
    public const WHERE_IN_EAGER_ALGORITHM = 'WHERE_IN_EAGER_ALGORITHM';

    /**
     * @var class-string<Model>|null
     */
    protected ?string $resultModelClass = null;

    /**
     * @var mixed|null
     */
    protected $explicitRejectValue = null;

    /**
     * @var mixed|null
     */
    protected $explicitResolveValue = null;

    /**
     * @var array<string, callable>
     */
    protected array $modifiers = [];

    /**
     * @var RelationExpression|null
     */
    protected ?RelationExpression $allowedGraphExpression = null;

    /**
     * @var array<string, mixed>
     */
    protected array $findOperationOptions = [];

    /**
     * @var string|null
     */
    protected $relatedQueryFor = null;

    /**
     * @var \Closure|null
     */
    private ?\Closure $findOperationFactory;

    private ?\Closure $insertOperationFactory;

    private ?\Closure $updateOperationFactory;

    private ?\Closure $patchOperationFactory;

    private ?\Closure $relateOperationFactory;

    private ?\Closure $unrelateOperationFactory;

    private ?\Closure $deleteOperationFactory;


    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(string $modelClass)
    {
        parent::__construct($modelClass);

        /** @var \Model $modelClass */
        $this->resultModelClass     = $modelClass;
        $this->findOperationOptions = $modelClass::getDefaultFindOptions();

        $this->findOperationFactory = function()
        { return new FindOperation('find'); };
        $this->insertOperationFactory = function()
        { return new InsertOperation('insert'); };
        $this->updateOperationFactory = function()
        { return new UpdateOperation('update'); };
        $this->patchOperationFactory = function()
        { return new UpdateOperation('patch', ['modelOptions' => ['patch' => true]]); };
        $this->deleteOperationFactory = function()
        { return new DeleteOperation('delete'); };
        $this->relateOperationFactory = function()
        { throw new \BadMethodCallException('Relate operation not supported'); };
        $this->unrelateOperationFactory = function()
        { throw new \BadMethodCallException('Unrelate operation not supported'); };
    }

    public function getFindOptions(): array
    {
        return $this->findOperationOptions;
    }

    public function setFindOption(string $key, $value): void
    {
        $this->findOperationOptions[$key] = $value;
    }

    /**
     * @return ModelSharQContextBase|ModelSharQContextUser
     */
    protected static function getSharQContext()
    {
        return new ModelSharQContextBase();
    }

    public static function parseRelationExpression(string $expression): RelationExpression
    {// 2023-08-01
        return RelationExpression::create($expression);
    }

    private static function checkEager(ModelSharQ $iBuilder): void
    {
        /** @var EagerOperation|null $eagerOperation */
        $eagerOperation = $iBuilder->findOperation(EagerOperation::class);

        if ($eagerOperation === null)
        {
            return;
        }

        $expression        = $eagerOperation->getExpression();
        $allowedExpression = $iBuilder->allowedGraphExpression();

        if (!$expression->isEmpty() && $allowedExpression !== null && !$allowedExpression->isSubExpression($expression))
        {
            throw new \InvalidArgumentException('Eager expression not allowed: '.$expression->getExpressionString());
        }
    }

    public function getTableNameFor(string $modelClassOrTableName): string
    {
        return parent::getTableNameFor(self::resolveTableName($modelClassOrTableName));
    }

    public function getTableName(?string $newTableName = null): string
    {
        /** @var \Model $modelClass */
        $modelClass = $this->getModelClass();

        return $this->getTableNameFor($modelClass::getTableName(), $newTableName);
    }

    public function getTableRef(): string
    {
        /** @var class-string<\Model> $modelClass */
        $modelClass = $this->getModelClass();

        return $this->getTableRefFor($modelClass);
    }

    public function getAliasFor(string $modelClassOrTableName, ?string $newTableName = null): ?string
    {
        return parent::getAliasFor(self::resolveTableName($modelClassOrTableName), $newTableName);
    }

    public function getAlias(?string $alias = null): ?string
    {
        /** @var \Model $modelClass */
        $modelClass = $this->getModelClass();

        return $this->getAliasFor($modelClass::getTableName(), $alias);
    }

    public function setAlias(?string $alias): void
    {
        /** @var \Model $modelClass */
        $modelClass = $this->getModelClass();
        $this->setAliasFor($modelClass::getTableName(), $alias);
    }

    public function alias(string $alias): self
    {
        $this->setAlias($alias);

        return $this;
    }

    public function setAliasFor(string $modelClassOrTableName, ?string $newTableName = null): self
    {
        return parent::setAliasFor(self::resolveTableName($modelClassOrTableName), $newTableName);
    }

    public function getFullIdColumnFor(string $modelClass)
    {
        /** @var class-string<\Model> $modelClass */
        $tableName = $this->getTableRefFor($modelClass);

        /** @var \Model $modelClass */
        $idColumn = $modelClass::getTableIDs();

        if (is_array($idColumn))
        {
            return array_map(fn($column) => $tableName.'.'.$column, $idColumn);
        }

        return $tableName.'.'.$idColumn;
    }

    public function getFullIdColumn()
    {
        $modelClass = $this->getModelClass();

        return $this->getFullIdColumnFor($modelClass);
    }

    public function withGraphFetched(string $relationExpression, array $options = []): self
    {
        throw new \Exception('withGraphFetched is not supported');
    }

    public function withGraphJoined(string $relationExpression, array $options = []): self
    {
        return $this->_withGraph($relationExpression, $options, static::JOIN_EAGER_ALGORITHM);
    }

    /**
     * @return class-string<Model>
     */
    private static function getOperationClassForEagerAlgorithm(self $iBuilder, string $eagerAlgorithm): string
    {
        if ($eagerAlgorithm === static::JOIN_EAGER_ALGORITHM)
        {
            return JoinEagerOperation::class;
        }

        if ($eagerAlgorithm === static::NAIVE_EAGER_ALGORITHM)
        {
            return NaiveEagerOperation::class;
        }

        if ($eagerAlgorithm === static::WHERE_IN_EAGER_ALGORITHM)
        {
            return WhereInEagerOperation::class;
        }

        throw new \Exception('Unknown eager algorithm: '.$eagerAlgorithm);
    }

    private static function &ensureEagerOperation(self $iBuilder, ?string $eagerAlgorithm = null): EagerOperation
    {
        /** @var \Model $modelClass */
        $modelClass          = $iBuilder->getModelClass();
        $defaultGraphOptions = $modelClass::getDefaultGraphOptions();
        $eagerOperation      = $iBuilder->findOperation(EagerOperation::class);

        if ($eagerAlgorithm !== null)
        {
            $eagerOperationClass = self::getOperationClassForEagerAlgorithm($iBuilder, $eagerAlgorithm);

            if ($eagerOperation instanceof $eagerOperationClass)
            {
                return $eagerOperation;
            }

            $newEagerOperation = new $eagerOperationClass('eager', ['defaultGraphOptions' => $defaultGraphOptions]);

            if ($eagerOperation !== null)
            {
                $newEagerOperation = clone $eagerOperation;
            } //$newEagerOperation->cloneFrom($eagerOperation);

            $iBuilder->clear(EagerOperation::class);
            $iBuilder->addOperation($newEagerOperation, []);

            return $newEagerOperation;
        }

        if ($eagerOperation !== null)
        {
            return $eagerOperation;
        }

        $eagerOperationClass = self::getOperationClassForEagerAlgorithm($iBuilder, static::WHERE_IN_EAGER_ALGORITHM);

        $newEagerOperation = new $eagerOperationClass('eager', ['defaultGraphOptions' => $defaultGraphOptions]);

        $iBuilder->addOperation($newEagerOperation, []);

        return $newEagerOperation;
    }

    private function _withGraph(string $relationExpression, array $options, string $eagerAlgorithm): self
    {
        $eagerOperation   = &self::ensureEagerOperation($this, $eagerAlgorithm);
        $parsedExpression = self::parseRelationExpression($relationExpression);

        $expression = $eagerOperation->getExpression();
        $expression = $expression->merge($parsedExpression);
        $eagerOperation->setExpression($expression);

        $graphOptions = $eagerOperation->getGraphOptions();
        $graphOptions = array_merge($graphOptions, $options);
        $eagerOperation->setGraphOptions($graphOptions);

        self::checkEager($this);

        return $this;
    }

    /**
     * @param string|RelationExpression $expression
     * @return ModelSharQ
     */
    public function allowGraph($expression): self
    {
        $currentExpression = $this->allowedGraphExpression ?? RelationExpression::create();

        $parsedExpression = self::parseRelationExpression($expression);

        $this->allowedGraphExpression = $currentExpression->merge($parsedExpression);

        self::checkEager($this);

        return $this;
    }

    protected function allowedGraphExpression(): ?RelationExpression
    {
        return $this->allowedGraphExpression;
    }

    protected function graphExpressionObject(): ?RelationExpression
    {
        /** @var EagerOperation|null $eagerOperation */
        $eagerOperation = $this->findOperation(EagerOperation::class);

        if ($eagerOperation === null || $eagerOperation->getExpression()->isEmpty())
        {
            return null;
        }

        return $eagerOperation->getExpression();
    }

    // protected function graphModifiersAtPath()

    /**
     * @param string $path
     * @param \Closure(SharQ|string|string[]) $modifier
     * @return ModelSharQ
     */
    public function modifyGraph(string $path, \Closure $modifier): self
    {
        $eagerOperation = self::ensureEagerOperation($this);

        $eagerOperation->addModifierAtPath($path, $modifier);

        return $this;
    }

    // protected function findOptions(array $options)
    
    protected function getResultModelClass(): string
    {
        return $this->resultModelClass ?? $this->getModelClass();
    }

    public function isFind(): bool
    {
        $isNotFind =
        (
            $this->isInsert() ||
            $this->isUpdate() ||
            $this->isDelete() ||
            $this->isRelate() ||
            $this->isUnrelate()
        );

        return !$isNotFind;
    }

    public function isInsert(): bool
    {
        return $this->has(InsertOperation::class);
    }

    public function isUpdate(): bool
    {
        return $this->has(UpdateOperation::class);
    }

    public function isDelete(): bool
    {
        return $this->has(DeleteOperation::class);
    }

    public function isRelate(): bool
    {
        return $this->has(RelateOperation::class);
    }

    public function isUnrelate(): bool
    {
        return $this->has(UnrelateOperation::class);
    }

    public function hasWheres(): bool
    {
        $queryClone        = clone $this;
        $queryWithoutGraph = $queryClone->clearWithGraph();

        $prebuildQuery = self::prebuildQuery($queryWithoutGraph);

        return $prebuildQuery->has(ModelSharQBase::WHERE_SELECTOR);
    }

    public function hasSelects(): bool
    {
        return $this->has(ModelSharQBase::SELECT_SELECTOR);
    }

    public function hasWithGraph(): bool
    {
        /** @var EagerOperation|null $iEagerOperation */
        $iEagerOperation = $this->findOperation(EagerOperation::class);

        if ($iEagerOperation === null)
        {
            return false;
        }

        return $iEagerOperation->hasExpression();
    }

    public function isSelectAll(): bool
    {
        if (count($this->operations) === 0)
        {
            return true;
        }

        $tableReference = $this->getTableRef();
        $tableName      = $this->getTableName();

        $result = $this->everyOperation(function($operation) use ($tableReference, $tableName)
        {
            if ($operation instanceof SelectOperation)
            {
                // SelectOperations with zero selections are the ones that only have raw items or other non-trivial selections.

                return $operation->hasSelections() && array_reduce($operation->getSelections(), function($carry, $selection) use ($tableReference)
                {
                    return $carry && (!$selection->getTable() || $selection->getTable() === $tableReference) && $selection->getColumn() === '*';
                }, true);
            }
            else if ($operation instanceof FromOperation)
            {
                return $operation->getTable() === $tableName;
            }
            else if ($operation->getName() === 'as' || $operation->is(FindOperation::class) || $operation->is(OnErrorOperation::class))
            {
                return true;
            }

            return false;
        });

        return $result;
    }

    public function clearWithGraph(): self
    {
        $this->clear(EagerOperation::class);

        return $this;
    }

    public function clearWithGraphFetched(): self
    {
        $this->clear(WhereInEagerOperation::class);

        return $this;
    }

    public function clearReject(): self
    {
        $this->explicitRejectValue = null;

        return $this;
    }

    public function clearAllowedGraph(): self
    {
        $this->allowedGraphExpression = null;

        return $this;
    }

    public function clearModifiers(): self
    {
        $this->modifiers = [];

        return $this;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function serModifiers(array $modifiers): self
    {
        $this->modifiers = $modifiers;

        return $this;
    }

    public function modifiers(?array $modifiers = null): self
    {
        if ($modifiers !== null)
        {
            $this->modifiers = $modifiers;
        }

        return $this;
    }

    /**
     * @param class-string<Model> $modelClass
     * @return ModelSharQ
     */
    public function castTo(?string $modelClass): self
    {
        $this->resultModelClass = $modelClass;

        return $this;
    }

    public function resultSize(...$args): int
    {
        $iSharQ   = $this->getSharQ();
        $iBuilder = clone $this;

        $iBuilder->clear(self::LIMIT_SELECTOR);
        $iBuilder->clear(self::ORDER_BY_SELECTOR);

        $countQuery = $iSharQ->count('* AS count')->from(function($q) use ($iBuilder)
        {
            $iBuilder->toSharQ($q)->as('temp');
        });

        $result = $countQuery->run(...$args);

        return $result[0] && $result[0]['count'] ? intval($result[0]['count']) : 0;
    }

    /**
     * @param SharQ|Join|null $iSharQ
     */
    public function toSharQ($iSharQ = null): SharQ
    {
        $iClonedBuilder = clone $this;

        $prebuildQuery = self::prebuildQuery($iClonedBuilder);

        return self::buildSharQQuery($prebuildQuery, $iSharQ);
    }

    private function prebuildQuery(ModelSharQ $iBuilder): ModelSharQ
    {
        $iBuilder = self::addImplicitOperations($iBuilder);
        $iBuilder = self::callOnBuildHooks($iBuilder);

        // $this->beforeExecute($iBuilder);
        // $iBuilder = self::callOnBuildHooks($iBuilder);

        $queryExecutorOperation = self::findQueryExecutorOperation($iBuilder);

        if ($queryExecutorOperation === null)
        {
            return $iBuilder;
        }

        return self::prebuildQuery($queryExecutorOperation->queryExecutor($iBuilder));
    }

    private function addImplicitOperations(self &$iBuilder): self
    {
        if ($iBuilder->isFind())
        {
            // If no write operations have been called at this point this query is a
            // find query and we need to call the custom find implementation.
            if (!$iBuilder->has(FindOperation::class))
            {
                $this->addFindOperation($iBuilder);
            }
        }

        if ($iBuilder->hasWithGraph())
        {
            self::moveEagerOperationToEnd($iBuilder);
        }

        return $iBuilder;
    }

    private function addFindOperation(self $iBuilder): void
    {
        $findOperationFactory = $this->getFindOperationFactory();

        $iFindOperation = $findOperationFactory($this);

        $iBuilder->addOperationToFront($iFindOperation, []);
    }

    private static function moveEagerOperationToEnd(self &$iBuilder): void
    {
        $iEagerOperation = $iBuilder->findOperation(EagerOperation::class);

        if ($iEagerOperation === null)
        {
            return;
        }

        $iBuilder->clear(EagerOperation::class);

        $iBuilder->addOperation($iEagerOperation, []);
    }

    private static function chainOperationHooks($results, ModelSharQ $iBuilder, string $hookName)
    {
        $iBuilder->forEachOperations(true, function(ModelSharQOperation $iOperation) use ($iBuilder, $hookName, &$results)
        {
            if (!$iOperation->hasHook($hookName))
            {
                return;
            }

            $results = $iBuilder->callOperationMethod($iOperation, $hookName, [$results]);

            return null;
        });

        return $results;
    }

    private static function chainHooks(ModelSharQ $iBuilder, $func)
    {
        if ($func instanceof \Closure)
        {
            return $func($iBuilder);
        }

        if (is_array($func))
        {
            foreach ($func as $iFunc)
            {
                $results = static::chainHooks($iBuilder, $iFunc);
            }

            return $results;
        }

        throw new \Exception('Invalid hook type');
    }

    private function beforeExecute(self &$iBuilder): void
    {
        $iBuilder = $this->addImplicitOperations($iBuilder);

        $this->chainOperationHooks(null, $iBuilder, 'onBefore1');

        $this->chainHooks($iBuilder, $iBuilder->getContext()->getRunBeforeCallback());
        $this->chainHooks($iBuilder, $iBuilder->getInternalContext()->getRunBeforeCallback());

        $this->chainOperationHooks(null, $iBuilder, 'onBefore2');
        $this->chainOperationHooks(null, $iBuilder, 'onBefore3');
    }

    /**
     * @param self $iBuilder
     * @param array|null $results
     * @return array|Model|null
     */
    private function afterExecute(self $iBuilder, $results)
    {
        $this->addImplicitOperations($iBuilder);

        $results = self::chainOperationHooks($results, $iBuilder, 'onAfter1');
        $results = self::chainOperationHooks($results, $iBuilder, 'onAfter2');

        self::chainHooks($iBuilder, $iBuilder->getContext()->getRunAfterCallback());
        self::chainHooks($iBuilder, $iBuilder->getInternalContext()->getRunAfterCallback());

        $results = self::chainOperationHooks($results, $iBuilder, 'onAfter3');

        return $results;
    }

    private static function callOnBuildFuncs(self $iBuilder, $func): void
    {
        if ($func instanceof \Closure)
        {
            $func($iBuilder);

            return;
        }

        if (is_array($func))
        {
            foreach ($func as $iFunc)
            {
                self::callOnBuildFuncs($iBuilder, $iFunc);
            }

            return;
        }

        throw new \Exception('Invalid hook type');
    }

    private static function callOnBuildHooks(self $iBuilder): self
    {
        self::callOnBuildFuncs($iBuilder, $iBuilder->getContext()->getOnBuildCallback());
        self::callOnBuildFuncs($iBuilder, $iBuilder->getInternalContext()->getOnBuildCallback());
        
        $iBuilder->executeOnBuild();

        return $iBuilder;
    }

    public static function findQueryExecutorOperation(self $iBuilder)
    {
        return $iBuilder->findOperation(function(ModelSharQOperation $iOperation)
        {
            $hasQueryExecutor = $iOperation->hasQueryExecutor();

            return $hasQueryExecutor;
        });
    }

    /**
     * 2023-07-10
     * @param string|class-string<Model> $modelClassOrTableName
     */
    private static function resolveTableName(string $modelClassOrTableName): string
    {
        if (is_subclass_of($modelClassOrTableName, Model::class))
        {
            /** @var \Model $modelClassOrTableName */
            return $modelClassOrTableName::getTableName();
        }

        return $modelClassOrTableName;
    }

    public function resolve($value): self
    {
        $this->explicitResolveValue = $value;

        return $this;
    }

    public function reject($value): self
    {
        $this->explicitRejectValue = $value;

        return $this;
    }

    public function getExplicitResolveValue()
    {
        return $this->explicitResolveValue;
    }

    public function getExplicitRejectValue()
    {
        return $this->explicitRejectValue;
    }

    public function isExplicitlyResolvedOrRejected(): bool
    {
        return $this->explicitResolveValue !== null || $this->explicitRejectValue !== null;
    }

    public function isExecuteable(): bool
    {
        return !$this->isExplicitlyResolvedOrRejected() && $this->findQueryExecutorOperation($this) === null;
    }

    private static function setDefaultTable(self $iBuilder, SharQ $iSharQ): SharQ
    {
        $table    = $iBuilder->getTableName();
        $tableRef = $iBuilder->getTableRef();

        if ($table === $tableRef)
        {
            return $iSharQ->table($table);
        }

        return $iSharQ->table([$tableRef => $table]);
    }

    private static function setDefaultSelect(self $iBuilder, SharQ $iSharQ): SharQ
    {
        $tableRef = $iBuilder->getTableRef();

        return $iSharQ->select($tableRef.'.*');
    }

    private static function buildSharQQuery(self $iBuilder, ?SharQ $iSharQ = null): SharQ
    {
        $iSharQ = $iSharQ ?? $iBuilder->getSharQ();
        $iBuilder->executeOnBuildSharQ($iSharQ);

        /** @var FromOperation|null $fromOperation */
        $fromOperation = $iBuilder->findLastOperation(ModelSharQBase::FROM_SELECTOR);

        if ($iBuilder->getIsPartial())
        {
            return $iSharQ;
        }

        // Set the table only if it hasn't been explicitly set yet.
        if ($fromOperation === null)
        {
            $iSharQ = self::setDefaultTable($iBuilder, $iSharQ);
        }

        $hasFromTable = $fromOperation !== null && $fromOperation->getTable() === null;
        $hasSelects   = $iBuilder->hasSelects();

        // Only add `table.*` select if there are no explicit selects and `from` is a table name and not a subquery.
        if (!$hasSelects && !$hasFromTable)
        {
            $iSharQ = self::setDefaultSelect($iBuilder, $iSharQ);
        }

        return $iSharQ;
    }

    private static function doExecute(self $iBuilder)
    {
        self::callOnBuildHooks($iBuilder);

        if ($iBuilder->isExplicitlyResolvedOrRejected())
        {
            return $iBuilder->getExplicitResolveValue();
        }

        $queryExecutorOperation = self::findQueryExecutorOperation($iBuilder);

        if ($queryExecutorOperation !== null)
        {
            $query = $queryExecutorOperation->queryExecutor($iBuilder);

            return $query;
        }

        $iSharQ = self::buildSharQQuery($iBuilder);

        $results = $iSharQ->run();

        $results = Utilities::arrayRemoveFalsey($results);

        $results = self::chainOperationHooks($results, $iBuilder, 'onRawResult');

        $iModels = self::createModels($results, $iBuilder);

        return $iModels;
    }

    private static function createModels($result, self $iBuilder): ?array
    {
        if ($result === null)
        {
            return null;
        }

        // results are applied to input models in `InsertOperation.onAfter1` instead.
        if ($iBuilder->isInsert())
        {
            return $result;
        }

        $modelClass = $iBuilder->getResultModelClass();

        if (is_array($result))
        {
            if (count($result) > 0 && self::shouldBeConvertedToModel($result[0], $modelClass))
            {
                foreach ($result as &$re)
                {
                    /** @var \Model $modelClass */
                    $re = $modelClass::createFromDatabaseArray($re);
                }
            }
        }
        else if (self::shouldBeConvertedToModel($result, $modelClass))
        {
            /** @var \Model $modelClass */
            $result = $modelClass::createFromDatabaseArray($result);
        }

        return $result;
    }

    private static function shouldBeConvertedToModel($result, string $modelClass): bool
    {
        return is_array($result) && count($result) > 0 && !(reset($result) instanceof $modelClass);
    }

    private static function handleExecuteException(self $iBuilder, \Exception $exceptions)
    {
        $result = null;

        $iBuilder->forEachOperations(self::ALL_SELECTOR, function(ModelSharQOperation $iOperation) use ($iBuilder, $exceptions, &$result)
        {
            if (!$iOperation->hasOnError())
            {
                return;
            }

            $result = $iOperation->onError($iBuilder, $exceptions);
        });

        $internalOptions = $iBuilder->getInternalOptions();

        if (!($internalOptions['returnError'] ?? false))
        {
            throw $exceptions;
        }

        return $result;
    }

    /**
     * @return Model[]|Model|null
     */
    public function run()
    {
        return $this->execute();
    }

    /**
     * @return Model[]|Model|null
     */
    private function execute()
    {
        $iBuilder = clone $this;

        try
        {
            self::beforeExecute($iBuilder);

            $results = self::doExecute($iBuilder);

            return $this->afterExecute($iBuilder, $results);
        }
        catch(\Exception $e)
        {
            return self::handleExecuteException($iBuilder, $e);
        }
    }

    // /**
    //  * @param mixed $data
    //  * @return ModelSharQ
    //  */
    // private function throwIfNotFound($data): self
    // {
    //     return $this->runAfter(function($result)
    //     {
    //         $isEmpty =
    //         (
    //             is_array($result) && count($result) === 0
    //             ||
    //             $result === null
    //             ||
    //             $result === 0
    //         );
    //
    //         if($isEmpty)
    //         {
    //             /** @var \Model $modelClass */
    //             $modelClass = $this->getModelClass();
    //             throw $modelClass::createNotFoundError($this->getContext(), $data);
    //         }
    //
    //         return $result;
    //     });
    // }

    private function findSelection($selection, bool $explicit = false): ?Selection
    {
        $noSelectStatements = true;
        $selectionInstance  = null;

        $this->forEachOperations(self::ALL_SELECTOR, function($operation) use (&$noSelectStatements, &$selectionInstance, $selection, $explicit)
        {
            if (!($operation instanceof SelectOperation))
            {
                return null;
            }

            $selectionInstance  = $operation->findSelection($this, $selection);
            $noSelectStatements = false;

            if ($selectionInstance === null)
            {
                return false;
            }

            return null;
        });

        if ($selectionInstance !== null)
        {
            return $selectionInstance;
        }

        if ($noSelectStatements && !$explicit)
        {
            $selectAll = new Selection($this->getTableRef(), '*');

            if (Selection::doesSelect($this, $selectAll, $selection))
            {
                return $selectAll;
            }

            return null;
        }

        return null;
    }

    public function findAllSelections(): array
    {
        $allSelections = [];

        $this->forEachOperations(self::ALL_SELECTOR, function($operation) use (&$allSelections)
        {
            if (!($operation instanceof SelectOperation))
            {
                return null;
            }

            $allSelections = array_merge($allSelections, $operation->getSelections());

            return null;
        });

        return $allSelections;
    }

    public function hasSelection($selection, bool $explicit = false): bool
    {
        return $this->findSelection($selection, $explicit) !== null;
    }

    public function hasSelectionAs($selection, $alias, bool $explicit = false): bool
    {
        /** @var Selection $selection */
        $selection = Selection::create($selection);

        /** @var Selection|null $foundSelection */
        $foundSelection = $this->findSelection($selection, $explicit);

        if ($foundSelection === null)
        {
            return false;
        }

        if ($foundSelection->getColumn() === '*')
        {
            return $selection->getColumn() === $alias;
        }

        return $foundSelection->getName() === $alias;
    }

    public function traverse($modelClass, $traverser = null)
    {
        if ($traverser === null)
        {
            $traverser  = $modelClass;
            $modelClass = null;
        }

        return $this->runAfter(function($result) use ($modelClass, $traverser)
        {
            /** @var \Model $resultModelClass */
            $resultModelClass = $this->getResultModelClass();

            $resultModelClass::traverse($modelClass, $result, $traverser);

            return $result;
        });
    }

    public function page(int $page, int $pageSize): self
    {
        if ($page < 0)
        {
            throw new \Exception('Page must be >= 0');
        }

        if ($pageSize < 0)
        {
            throw new \Exception('Page size must be >= 0');
        }

        return $this->range($page * $pageSize, ($page + 1) * $pageSize - 1);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): self
    {
        $table = $args[0]['table'] ?? $this->getTableName();

        $iSharQ = $this->getSharQ();

        $tableParts = explode('.', $table);

        $internalOptions = $this->getInternalOptions();

        // TODO: Implement columnInfo
        $columnInfoQuery = $iSharQ->table(end($tableParts))->columnInfo();
        $schema          = $internalOptions['schema'] ?? null;

        if ($schema === null && count($tableParts) > 1)
        {
            $schema = $tableParts[0];
        }

        if ($schema !== null)
        {
            $columnInfoQuery->withSchema($schema);
        }

        if ($internalOptions['debug'] ?? false)
        {
            $columnInfoQuery->debug(true);
        }

        return $columnInfoQuery;
    }

    public function withSchema($schema): self
    {
        $internalOptions           = $this->getInternalOptions();
        $internalOptions['schema'] = $schema;
        $this->setInternalOptions($internalOptions);

        $context = $this->getInternalContext();

        $context->addOnBuildCallback(function($iBuilder) use ($schema)
        {
            if (!$iBuilder->has('/^withSchema$/'))
            {
                $iBuilder->addOperationToFront(new SharQOperation('withSchema'), [$schema]);
            }
        });

        return $this;
    }

    public function debug /* istanbul ignore next */(bool $doIt = true): self
    {
        $internalOptions          = $this->getInternalOptions();
        $internalOptions['debug'] = $doIt;
        $this->setInternalOptions($internalOptions);

        $context = $this->getInternalContext();

        $context->addOnBuildCallback(function($iBuilder) use ($doIt)
        {
            $iBuilder->addOperation(new SharQOperation('debug'), [$doIt]);
        });

        return $this;
    }

    public function returnError(bool $doIt = true): self
    {
        $internalOptions                = $this->getInternalOptions();
        $internalOptions['returnError'] = $doIt;
        $this->setInternalOptions($internalOptions);

        return $this;
    }

    private static function writeOperation(ModelSharQ $iBuilder, \Closure $callback): self
    {
        if (!$iBuilder->isFind())
        {
            throw new \Exception('Double call to a write method. You can only call one of the write methods (insert, update, patch, delete, relate, unrelate, increment, decrement) and only once per query builder.');
        }

        $callback();

        return $iBuilder;
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @return ModelSharQ
     */
    public function insert($modelsOrObjects): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects)
        {
            $insertOperationFactory = $this->getInsertOperationFactory();

            $iInsertOperation = $insertOperationFactory($this);

            $this->addOperation($iInsertOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @return ModelSharQ
     */
    public function insertAndFetch($modelsOrObjects): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects)
        {
            $insertOperationFactory = $this->getInsertOperationFactory();

            $iInsertOperation = $insertOperationFactory($this);

            $insertAndFetchOperation = new InsertAndFetchOperation('insertAndFetch', ['delegate' => $iInsertOperation]);

            $this->addOperation($insertAndFetchOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @param array $opt
     * @return ModelSharQ
     */
    public function insertGraph($modelsOrObjects, $opt): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects, $opt)
        {
            $insertOperationFactory = $this->getInsertOperationFactory();

            $iInsertOperation = $insertOperationFactory($this);

            $insertGraphOperation = new InsertGraphOperation('insertGraph', ['delegate' => $iInsertOperation, 'options' => $opt]);

            $this->addOperation($insertGraphOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @param array $opt
     * @return ModelSharQ
     */
    public function insertGraphAndFetch($modelsOrObjects, $opt): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects, $opt)
        {
            $insertOperationFactory = $this->getInsertOperationFactory();

            $iInsertOperation = $insertOperationFactory($this);

            $insertGraphOperation = new InsertGraphOperation('insertGraph', ['delegate' => $iInsertOperation, 'options' => $opt]);

            $insertGraphAndFetchOperation = new InsertGraphAndFetchOperation('insertGraphAndFetch', ['delegate' => $insertGraphOperation]);

            $this->addOperation($insertGraphAndFetchOperation, [$modelsOrObjects]);
        });
    }

    public function update($modelOrObject): self
    {
        return self::writeOperation($this, function() use ($modelOrObject)
        {
            $updateOperationFactory = $this->getUpdateOperationFactory();

            $iUpdateOperation = $updateOperationFactory($this);

            $this->addOperation($iUpdateOperation, [$modelOrObject]);
        });
    }

    public function updateAndFetch($modelOrObject): self
    {
        return self::writeOperation($this, function() use ($modelOrObject)
        {
            $updateOperationFactory = $this->getUpdateOperationFactory();

            $iUpdateOperation = $updateOperationFactory($this);
            
            $modelClass = $this->getModelClass();

            if (!($iUpdateOperation instanceof $modelClass))
            {
                throw new \Exception('updateAndFetch can only be called for instance operations');
            }

            $updateAndFetchOperation = new UpdateAndFetchOperation('updateAndFetch', ['delegate' => $iUpdateOperation]);

            $this->addOperation($updateAndFetchOperation, [$modelOrObject]);
        });
    }

    public function updateAndFetchById($id, $modelOrObject): self
    {
        return self::writeOperation($this, function() use ($id, $modelOrObject)
        {
            $updateOperationFactory = $this->getUpdateOperationFactory();

            $iUpdateOperation = $updateOperationFactory($this);
            
            $updateAndFetchOperation = new UpdateAndFetchOperation('updateAndFetch', ['delegate' => $iUpdateOperation]);

            $this->addOperation($updateAndFetchOperation, [$id, $modelOrObject]);
        });
    }

    public function upsertGraph($modelsOrObjects, $upsertOptions): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects, $upsertOptions)
        {
            $upsertOperation = new UpsertGraphOperation('upsertGraph', ['upsertOptions' => $upsertOptions]);

            $this->addOperation($upsertOperation, [$modelsOrObjects]);
        });
    }

    public function upsertGraphAndFetch($modelsOrObjects, $upsertOptions): self
    {
        return self::writeOperation($this, function() use ($modelsOrObjects, $upsertOptions)
        {
            $upsertOperation = new UpsertGraphOperation('upsertGraph', ['upsertOptions' => $upsertOptions]);

            $upsertAndFetchOperation = new UpsertGraphAndFetchOperation('upsertGraphAndFetch', ['delegate' => $upsertOperation]);

            $this->addOperation($upsertAndFetchOperation, [$modelsOrObjects]);
        });
    }

    public function patch($modelOrObject): self
    {
        return static::writeOperation($this, function() use ($modelOrObject)
        {
            $patchOperationFactory = $this->getPatchOperationFactory();

            $iPatchOperation = $patchOperationFactory($this);

            $this->addOperation($iPatchOperation, [$modelOrObject]);
        });
    }

    /**
     * @param Model|object $modelOrObject
     * @return ModelSharQ
     */
    public function patchAndFetch($modelOrObject): self
    {
        return self::writeOperation($this, function() use ($modelOrObject)
        {
            $patchOperationFactory = $this->getPatchOperationFactory($this);

            /** @var PatchOperation $iPatchOperation */
            $iPatchOperation = $patchOperationFactory($this);

            $modelClass = $this->getModelClass();

            if (!($iPatchOperation instanceof $modelClass))
            {
                throw new \Exception('patchAndFetch can only be called for instance operations');
            }

            $patchAndFetchOperation = new UpdateAndFetchOperation('patchAndFetch', ['delegate' => $iPatchOperation]);

            //$iPatchOperation is an instance update operation that already adds the
            // required "where id = $" clause.
            $patchAndFetchOperation->skipIdWhere(true);

            $this->addOperation($patchAndFetchOperation, [$iPatchOperation->getInstance()->getID(), $modelOrObject]);
        });
    }

    public function patchAndFetchById($id, $modelOrObject): self
    {
        return self::writeOperation($this, function() use ($id, $modelOrObject)
        {
            $patchOperationFactory = $this->getPatchOperationFactory();

            $iPatchOperation = $patchOperationFactory($this);

            $patchAndFetchOperation = new UpdateAndFetchOperation('patchAndFetch', ['delegate' => $iPatchOperation]);

            $this->addOperation($patchAndFetchOperation, [$id, $modelOrObject]);
        });
    }

    public function delete(...$args): self
    {
        return self::writeOperation($this, function() use ($args)
        {
            if (count($args) !== 0)
            {
                throw new \Exception("Don't pass arguments to delete(). You should use it like this: delete()->where('foo', 'bar')->andWhere(...)");
            }

            $deleteOperationFactory = $this->getDeleteOperationFactory();

            $iDeleteOperation = $deleteOperationFactory($this);

            $this->addOperation($iDeleteOperation, $args);
        });
    }

    public function del(...$args): self
    {
        return $this->delete(...$args);
    }

    public function relate(...$args): self
    {
        return self::writeOperation($this, function() use ($args)
        {
            $relateOperationFactory = $this->getRelateOperationFactory();

            $iRelateOperation = $relateOperationFactory($this);

            $this->addOperation($iRelateOperation, $args);
        });
    }

    public function unrelate(...$args): self
    {
        return self::writeOperation($this, function() use ($args)
        {
            if (count($args) !== 0)
            {
                throw new \Exception("Don't pass arguments to unrelate(). You should use it like this: unrelate()->where('foo', 'bar')->andWhere(...)");
            }

            $unrelateOperationFactory = $this->getUnrelateOperationFactory();

            $iUnrelateOperation = $unrelateOperationFactory($this);

            $this->addOperation($iUnrelateOperation, $args);
        });
    }

    public function increment($propertyName, $howMuch): self
    {
        /** @var Model $modelClass */
        $modelClass = $this->getModelClass();
        $columnName = $modelClass::propertyNameToColumnName($propertyName);

        return $this->patch([$columnName => new Raw('?? + ?', $columnName, $howMuch)]);
    }

    public function decrement($propertyName, $howMuch): self
    {
        /** @var Model $modelClass */
        $modelClass = $this->getModelClass();
        $columnName = $modelClass::propertyNameToColumnName($propertyName);

        return $this->patch([$columnName => new Raw('?? - ?', $columnName, $howMuch)]);
    }

    public function findOne(...$args): self
    {
        return $this->where(...$args)->first();
    }

    public function range(...$args): self
    {
        $this->clear(RangeOperation::class);

        return $this->addOperation(new RangeOperation('range'), $args);
    }

    public function first(...$args): self
    {
        return $this->addOperation(new FirstOperation('first'), $args);
    }

    private static function ensureJoinRelatedOperation(self $iBuilder, string $joinOperation): JoinRelatedOperation
    {
        $operationName = $joinOperation.'Related';

        $operation = $iBuilder->findOperation($operationName);

        if ($operation === null)
        {
            $operation = new JoinRelatedOperation($operationName, ['joinOperation' => $joinOperation]);
            $iBuilder->addOperation($operation, []);
        }

        return $operation;
    }

    public function joinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'innerJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function innerJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'innerJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function outerJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'outerJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function fullOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'fullOuterJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function leftJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'leftJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function leftOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'leftOuterJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function rightJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'rightJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function rightOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'rightOuterJoin')->addCall(['expression' => $expression, 'options' => $options]);

        return $this;
    }

    public function deleteById($id): self
    {
        return $this->findById($id)
            ->delete();
    }

    public function findById(...$args): self
    {
        return $this->addOperation(new FindByIdOperation('findById'), $args)->first();
    }

    public function findByIds(...$args): self
    {
        return $this->addOperation(new FindByIdsOperation('findByIds'), $args);
    }

    public function runBefore(...$args): self
    {
        return $this->addOperation(new RunBeforeOperation('runBefore'), $args);
    }

    public function onBuild(...$args): self
    {
        return $this->addOperation(new OnBuildOperation('onBuild'), $args);
    }

    public function onBuildSharQ(...$args): self
    {
        return $this->addOperation(new OnBuildSharQOperation('onBuildSharQ'), $args);
    }

    public function runAfter(...$args): self
    {
        return $this->addOperation(new RunAfterOperation('runAfter'), $args);
    }

    public function onError(...$args): self
    {
        return $this->addOperation(new OnErrorOperation('onError'), $args);
    }

    public function from(...$args): self
    {
        return $this->addOperation(new FromOperation('from'), $args);
    }

    public function table(...$args): self
    {
        return $this->addOperation(new FromOperation('table'), $args);
    }

    public function for($relatedQueryFor = null)
    {
        if ($relatedQueryFor === null)
        {
            return $this->relatedQueryFor;
        }
        
        $this->relatedQueryFor = $relatedQueryFor;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function findOperationFactory(\Closure $factory): self
    {
        $this->findOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function insertOperationFactory(\Closure $factory): self
    {
        $this->insertOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function updateOperationFactory(\Closure $factory): self
    {
        $this->updateOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function patchOperationFactory(\Closure $factory): self
    {
        $this->patchOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function relateOperationFactory(\Closure $factory): self
    {
        $this->relateOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function unrelateOperationFactory(\Closure $factory): self
    {
        $this->unrelateOperationFactory = $factory;

        return $this;
    }

    /**
     * 2023-07-10
     * @param \Closure $factory
     * @return ModelSharQ
     */
    public function deleteOperationFactory(\Closure $factory): self
    {
        $this->deleteOperationFactory = $factory;

        return $this;
    }
    
    public function getFindOperationFactory(): ?\Closure
    {
        return $this->findOperationFactory;
    }

    public function getInsertOperationFactory(): ?\Closure
    {
        return $this->insertOperationFactory;
    }

    public function getUpdateOperationFactory(): ?\Closure
    {
        return $this->updateOperationFactory;
    }

    public function getDeleteOperationFactory(): ?\Closure
    {
        return $this->deleteOperationFactory;
    }

    public function getPatchOperationFactory(): ?\Closure
    {
        return $this->patchOperationFactory;
    }

    public function getRelateOperationFactory(): ?\Closure
    {
        return $this->relateOperationFactory;
    }

    public function getUnrelateOperationFactory(): ?\Closure
    {
        return $this->unrelateOperationFactory;
    }

    public function __clone()
    {
        foreach (get_object_vars($this) as $name => $value)
        {
            if (is_object($value))
            {
                $this->{$name} = clone $value;
            }
        }
    }
}
