<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\RunBeforeOperation;
use Sharksmedia\Objection\Operations\RunAfterOperation;

use Sharksmedia\Objection\Operations\OnBuildOperation;
use Sharksmedia\Objection\Operations\OnBuildQueryBuilderOperation;

use Sharksmedia\Objection\Operations\ModelQueryBuilderOperation;
use Sharksmedia\Objection\Operations\InsertOperation;
use Sharksmedia\Objection\Operations\InsertAndFetchOperation;
use Sharksmedia\Objection\Operations\InsertGraphOperation;
use Sharksmedia\Objection\Operations\InsertGraphAndFetchOperation;

use Sharksmedia\Objection\Operations\UpdateOperation;
use Sharksmedia\Objection\Operations\UpdateAndFetchOperation;

use Sharksmedia\Objection\Operations\RangeOperation;
use Sharksmedia\Objection\Operations\FirstOperation;

use Sharksmedia\Objection\Operations\DeleteOperation;

use Sharksmedia\Objection\Operations\SelectOperation;
use Sharksmedia\Objection\Operations\Selection;
use Sharksmedia\Objection\Operations\FindOperation;
use Sharksmedia\Objection\Operations\FindByIdOperation;
use Sharksmedia\Objection\Operations\FindByIdsOperation;

use Sharksmedia\Objection\Operations\RelateOperation;
use Sharksmedia\Objection\Operations\UnrelateOperation;
use Sharksmedia\Objection\Operations\EagerOperation;

use Sharksmedia\Objection\Operations\JoinEagerOperation;
use Sharksmedia\Objection\Operations\JoinRelatedOperation;

use Sharksmedia\Objection\Operations\NaiveEagerOperation;
use Sharksmedia\Objection\Operations\FromOperation;
use Sharksmedia\Objection\Operations\OnErrorOperation;
use Sharksmedia\Objection\Operations\QueryBuilderOperation;
use Sharksmedia\Objection\Operations\WhereInEagerOperation;

use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Raw;

class ModelQueryBuilder extends ModelQueryBuilderBase
{
    public const JOIN_EAGER_ALGORITHM = 'JOIN_EAGER_ALGORITHM';
    public const NAIVE_EAGER_ALGORITHM = 'NAIVE_EAGER_ALGORITHM';
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
     * @var array<string, mixed>
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
    protected ?string $relatedQueryFor = null;


    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(string $modelClass)
    {
        parent::__construct($modelClass);

        $this->resultModelClass = $modelClass;
        $this->findOperationOptions = $modelClass::getDefaultFindOptions();
    }

    /**
     * @return ModelQueryBuilderContextBase|ModelQueryBuilderContextUser
     */
    protected static function getQueryBuilderContext()
    {
        return new ModelQueryBuilderContextBase();
    }

    protected static function parseRelationExpression(string $expression): RelationExpression
    {
        return RelationExpression::create($expression);
    }

    private static function checkEager(ModelQueryBuilder $iBuilder): void
    {
        /** @var EagerOperation|null $eagerOperation */
        $eagerOperation = $iBuilder->findOperation(EagerOperation::class);

        if($eagerOperation === null) return;

        $expression = $eagerOperation->getExpression();
        $allowedExpression = $iBuilder->allowedGraphExpression();

        if(!$expression->isEmpty() && $allowedExpression !== null && $allowedExpression->isSubExpression($expression))
        {
            throw new \InvalidArgumentException('Eager expression not allowed: '.$expression);
        }
    }

    public function getTableNameFor(string $modelClassOrTableName, ?string $newTableName=null): string
    {
        return parent::getTableNameFor(self::resolveTableName($modelClassOrTableName), $newTableName);
    }

    public function getTableName(?string $newTableName=null): string
    {
        $modelClass = $this->getModelClass();
        return $this->getTableNameFor($modelClass::getTableName(), $newTableName);
    }

    public function getTableRef(): string
    {
        $modelClass = $this->getModelClass();
        return $this->getTableRefFor($modelClass);
    }

    public function getAliasFor(string $modelClassOrTableName, ?string $newTableName=null): ?string
    {
        return parent::getAliasFor(self::resolveTableName($modelClassOrTableName), $newTableName);
    }

    public function getAlias(string $alias): ?string
    {
        $modelClass = $this->getModelClass();
        return $this->getAliasFor($modelClass::getTableName(), $alias);
    }

    public function getFullIdColumnFor(string $modelClass)
    {
        $tableName = $this->getTableRefFor($modelClass);
        $idColumn = $modelClass::getIdColumn();

        if(is_array($idColumn)) return array_map(fn($column) => $tableName.'.'.$column, $idColumn);

        return $tableName.'.'.$idColumn;
    }

    public function getFullIdColumn()
    {
        $modelClass = $this->getModelClass();
        return $this->getFullIdColumnFor($modelClass);
    }

    public function withGraphFetched(string $relationExpression, array $options=[]): self
    {
        throw new \Exception('Not Supported');
    }

    public function withGraphJoined(string $relationExpression, array $options=[]): self
    {
        return $this->_withGraph($relationExpression, $options, static::JOIN_EAGER_ALGORITHM);
    }

    /**
     * @return class-string<Model>
     */
    private static function getOperationClassForEagerAlgorithm(self $iBuilder, string $eagerAlgorithm): string
    {
        if($eagerAlgorithm === static::JOIN_EAGER_ALGORITHM) return JoinEagerOperation::class;
        if($eagerAlgorithm === static::NAIVE_EAGER_ALGORITHM) return NaiveEagerOperation::class;
        if($eagerAlgorithm === static::WHERE_IN_EAGER_ALGORITHM) return WhereInEagerOperation::class;

        throw new \Exception('Unknown eager algorithm: '.$eagerAlgorithm);
    }

    private static function ensureEagerOperation(self $iBuilder, ?string $eagerAlgorithm=null): EagerOperation
    {
        $modelClass = $iBuilder->getModelClass();
        $defaultGraphOptions = $modelClass::getDefaultGraphOptions();
        $eagerOperation = $iBuilder->findOperation(EagerOperation::class);

        if($eagerAlgorithm !== null)
        {
            $eagerOperationClass = self::getOperationClassForEagerAlgorithm($iBuilder, $eagerAlgorithm);

            if($eagerOperation instanceof $eagerOperationClass) return $eagerOperation;

            $newEagerOperation = new EagerOperation('eager', ['defaultGraphOptions' => $defaultGraphOptions]);

            if($eagerOperation !== null) $newEagerOperation = clone $eagerOperation; //$newEagerOperation->cloneFrom($eagerOperation);

            $iBuilder->clear(EagerOperation::class);
            $iBuilder->addOperation($newEagerOperation);

            return $newEagerOperation;
        }

        if($eagerOperation !== null) return $eagerOperation;

        $eagerOperationClass = self::getOperationClassForEagerAlgorithm($iBuilder, static::WHERE_IN_EAGER_ALGORITHM);

        $newEagerOperation = new EagerOperation('eager', ['defaultGraphOptions' => $defaultGraphOptions]);

        $iBuilder->addOperation($newEagerOperation);
    }

    private function _withGraph(string $relationExpression, array $options, string $eagerAlgorithm): self
    {
        $eagerOperation = self::ensureEagerOperation($this, $eagerAlgorithm);
        $parsedExpression = self::parseRelationExpression($this->getModelClass(), $relationExpression);

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
     * @return ModelQueryBuilder
     */
    public function allowGraph($expression): self
    {
        $currentExpression = $this->allowedGraphExpression ?? RelationExpression::create();

        $parsedExpression = self::parseRelationExpression($this->getModelClass(), $expression);

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

        if($eagerOperation === null || $eagerOperation->getExpression()->isEmpty()) return null;

        return $eagerOperation->getExpression();
    }

    // protected function graphModifiersAtPath()

    /**
     * @param string $path
     * @param \Closure(QueryBuilder|string|string[]) $modifier
     * @return ModelQueryBuilder
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

    private function isFind(): bool
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

    private function isInsert(): bool
    {
        return $this->has(InsertOperation::class);
    }

    private function isUpdate(): bool
    {
        return $this->has(UpdateOperation::class);
    }

    private function isDelete(): bool
    {
        return $this->has(DeleteOperation::class);
    }

    private function isRelate(): bool
    {
        return $this->has(RelateOperation::class);
    }

    private function isUnrelate(): bool
    {
        return $this->has(UnrelateOperation::class);
    }

    private function hasWheres(): bool
    {
        $queryClone = clone $this;
        $queryWithoutGraph = $queryClone->clearWithGraph();

        return self::prebuildQuery($queryClone)->has(QueryBuilderBase::WHERE_SELECTOR);
    }

    private function hasSelects(): bool
    {
        return $this->has(ModelQueryBuilderBase::SELECT_SELECTOR);
    }

    private function hasWithGraph(): bool
    {
        /** @var EagerOperation|null $iEagerOperation */
        $iEagerOperation = $this->findOperation(EagerOperation::class);

        if($iEagerOperation === null) return false;

        return $iEagerOperation->hasExpression();
    }

    private function isSelectAll(): bool
    {
        if(count($this->operations) === 0) return true;

        $tableReference = $this->getTableRef();
        $tableName = $this->getTableName();

        return $this->everyOperation(function($operation) use($tableReference, $tableName)
        {
            if($operation instanceof SelectOperation)
            {
                // SelectOperations with zero selections are the ones that only have raw items or other non-trivial selections.

                return $operation->hasSelections() && array_reduce($operation->getSelections(), function($carry, $selection) use($tableReference)
                {
                    return $carry && (!$selection->getTable() || $selection->getTable() === $tableReference) && $selection->getColumn() === '*';
                }, true);
            }
            else if($operation instanceof FromOperation)
            {
                return $operation->getTable() === $tableName;
            }
            else if($operation->name === 'as' || $operation->is(FindOperation::class) || $operation->is(OnErrorOperation::class))
            {
                return true;
            }

            return false;
        });
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

    /**
     * @param class-string<Model> $modelClass
     * @return ModelQueryBuilder
     */
    public function castTo(?string $modelClass): self
    {
        $this->resultModelClass = $modelClass;

        return $this;
    }

    public function resultSize()
    {
        $iQueryBuilder = $this->getQueryBuilder();

        $iBuilder = clone $this;
        $iBuilder->clear('/orderBy|offset|limit/');

        $countQuery = $iQueryBuilder->count('* as count')->from(function($q) use($iBuilder)
        {
            $iBuilder->toQueryBuilder($q)->as('temp');
        });

        // $internalOptions = $this->getInternalOptions();
        // if($internalOptions['debug']) $countQuery->debug(true);

        $result = $countQuery->run();

        return $result[0]['count'] ?? 0;
    }

    public function toQueryBuilder(?QueryBuilder $iQueryBuilder=null): QueryBuilder
    {
        $prebuildQuery = self::prebuildQuery(clone $this);

        return self::buildQueryBuilderQuery($prebuildQuery, $iQueryBuilder);
    }

    private static function prebuildQuery(ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        $iBuilder = self::addImplicitOperations($iBuilder);
        $iBuilder = self::callOnBuildHooks($iBuilder);

        $queryExecutorOperation = self::findQueryExecutorOperation($iBuilder);

        if($queryExecutorOperation === null) return $iBuilder;

        return self::prebuildQuery($queryExecutorOperation->queryExecutor($iBuilder));
    }

    private static function addImplicitOperations(self $iBuilder): self
    {
        if($iBuilder->isFind())
        {
            // If no write operations have been called at this point this query is a
            // find query and we need to call the custom find implementation.
            if(!$iBuilder->has(FindOperation::class)) self::addFindOperation($iBuilder);
        }

        if($iBuilder->hasWithGraph()) self::moveEagerOperationToEnd($iBuilder);

        return $iBuilder;
    }

    private static function addFindOperation(self $iBuilder): void
    {
        $iFindOperation = static::findOperationFactory();

        $iBuilder->addOperationToFront($iFindOperation, []);
    }

    private static function moveEagerOperationToEnd(self $iBuilder): void
    {
        $iEagerOperation = $iBuilder->findOperation(EagerOperation::class);

        if($iEagerOperation === null) return;

        $iBuilder->clear(EagerOperation::class);

        $iBuilder->addOperation($iEagerOperation, []);
    }

    private static function chainOperationHooks($results, ModelQueryBuilder $iBuilder, string $hookName)
    {
        $iBuilder->forEachOperations(true, function(ModelQueryBuilderOperation $iOperation) use ($iBuilder, $hookName, &$results)
        {
            if(!$iOperation->hasHook($hookName)) return;

            $iBuilder->callOperationMethod($iOperation, $hookName, [$iBuilder, $results]);
        });
    }

    private static function chainHooks(ModelQueryBuilder $iBuilder, $func)
    {
        if($func instanceof \Closure) return $func($iBuilder);

        if(is_array($func))
        {
            foreach($func as $iFunc)
            {
                $results = static::chainHooks($iBuilder, $iFunc);
            }

            return $results;
        }

        throw new \Exception('Invalid hook type');
    }

    private static function beforeExecute(self $iBuilder): void
    {
        self::addImplicitOperations($iBuilder);

        self::chainOperationHooks(null, $iBuilder, 'onBefore1');

        self::chainHooks($iBuilder, $iBuilder->getContext()->getRunBeforeCallback());
        self::chainHooks($iBuilder, $iBuilder->getInternalContext()->getRunBeforeCallback());

        self::chainOperationHooks(null, $iBuilder, 'onBefore2');
        self::chainOperationHooks(null, $iBuilder, 'onBefore3');
    }

    private static function afterExecute(self $iBuilder): void
    {
        self::addImplicitOperations($iBuilder);

        self::chainOperationHooks(null, $iBuilder, 'onAfter1');
        self::chainOperationHooks(null, $iBuilder, 'onAfter2');

        self::chainHooks($iBuilder, $iBuilder->getContext()->getRunAfterCallback());
        self::chainHooks($iBuilder, $iBuilder->getInternalContext()->getRunAfterCallback());

        self::chainOperationHooks(null, $iBuilder, 'onAfter3');
    }

    private static function callOnBuildFuncs(self $iBuilder, $func): void
    {
        if($func instanceof \Closure)
        {
            $func($iBuilder);

            return;
        }

        if(is_array($func))
        {
            foreach($func as $iFunc) self::callOnBuildFuncs($iBuilder, $iFunc);

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

    private static function findQueryExecutorOperation(self $iBuilder)
    {
        return $iBuilder->findOperation(function(ModelQueryBuilderOperation $iOperation)
        {
            return $iOperation->hasQueryExecutor();
        });
    }

    /**
     * 2023-07-10
     * @param string|class-string<Model> $modelClassOrTableName
     */
    private static function resolveTableName(string $modelClassOrTableName): string
    {
        if(is_string($modelClassOrTableName)) return $modelClassOrTableName;

        return $modelClassOrTableName::getTableName();
    }

    private static function setDefaultTable(self $iBuilder, QueryBuilder $iQueryBuilder): QueryBuilder
    {
        $table = $iBuilder->getTableName();
        $tableRef = $iBuilder->getTableRef();

        if($table === $tableRef) return $iQueryBuilder->table($table);

        return $iQueryBuilder->table([$tableRef=>$table]);
    }

    private static function setDefaultSelect(self $iBuilder, QueryBuilder $iQueryBuilder): QueryBuilder
    {
        $tableRef = $iBuilder->getTableRef();

        return $iQueryBuilder->select($tableRef.'.*');
    }

    private static function buildQueryBuilderQuery(self $iBuilder, ?QueryBuilder $iQueryBuilder=null): QueryBuilder
    {
        $iQueryBuilder = $iQueryBuilder ?? $iBuilder->getQueryBuilder();
        $iBuilder->executeOnBuildQueryBuilder($iQueryBuilder);

        $fromOperation = $iBuilder->findLastOperation(ModelQueryBuilderBase::FROM_SELECTOR);

        if($iBuilder->getIsPartial()) return $iQueryBuilder;

        // Set the table only if it hasn't been explicitly set yet.
        if($fromOperation === null) $iQueryBuilder = self::setDefaultTable($iBuilder, $iQueryBuilder);

        // Only add `table.*` select if there are no explicit selects and `from` is a table name and not a subquery.
        if(!$iBuilder->hasSelects() && ($fromOperation === null || $fromOperation->getTable() !== null)) $iQueryBuilder = self::setDefaultSelect($iBuilder, $iQueryBuilder);

        return $iQueryBuilder;
    }

    private static function doExecute(self $iBuilder)
    {
        self::callOnBuildHooks($iBuilder);

        $queryExecutorOperation = self::findQueryExecutorOperation($iBuilder);

        if($queryExecutorOperation !== null) return $queryExecutorOperation->queryExecutor($iBuilder);

        $iQueryBuilder = self::buildQueryBuilderQuery($iBuilder);

        $results = $iQueryBuilder->run();

        self::chainOperationHooks($results, $iBuilder, 'onRawResult');
        $iModels = self::createModels($results, $iBuilder);

        return $iModels;
    }

    private static function createModels($result, self $iBuilder): ?array
    {
        if($result === null) return null;

        // results are applied to input models in `InsertOperation.onAfter1` instead.
        if($iBuilder->isInsert()) return $result;

        $modelClass = $iBuilder->getResultModelClass();

        if(is_array($result))
        {
            if(count($result) > 0 && self::shouldBeConvertedToModel($result[0], $modelClass))
            {
                foreach($result as &$re)
                {
                    $re = $modelClass::createFromDatabaseArray($re);
                }
            }
        }
        else if(self::shouldBeConvertedToModel($result, $modelClass))
        {
            $result = $modelClass::createFromDatabaseArray($result);
        }

        return $result;
    }

    private static function shouldBeConvertedToModel(?array $result, string $modelClass): bool
    {
        return is_array($result) && count($result) > 0 && !($result[0] instanceof $modelClass);
    }

    private static function handleExecuteException(self $iBuilder, \Exception $e)
    {
        self::chainOperationHooks($e, $iBuilder, 'onError');

        throw $e;
    }

    public function execute()
    {
        $iBuilder = clone $this;

        try
        {
            self::beforeExecute($iBuilder);

            $results = self::doExecute($iBuilder);

            return self::afterExecute($iBuilder, $results);
        }
        catch(\Exception $e)
        {
            return self::handleExecuteException($iBuilder, $e);
        }
    }

    /**
     * @param mixed $data
     * @return ModelQueryBuilder
     */
    private function throwIfNotFound($data): self
    {
        return $this->runAfter(function($result)
        {
            $isEmpty =
            (
                is_array($result) && count($result) === 0
                ||
                $result === null
                ||
                $result === 0
            );

            if($isEmpty)
            {
                $modelClass = $this->getModelClass();
                throw $modelClass::createNotFoundError($this->getContext(), $data);
            }

            return $result;
        });
    }

    private function findSelection($selection, bool $explicit=false): ?Selection
    {
        $noSelectStatements = true;
        $selectionInstance = null;

        $this->forEachOperations(true, function($operation) use(&$noSelectStatements, &$selectionInstance, $selection, $explicit)
            {
                if(!($operation instanceof SelectOperation)) return;

                $selectionInstance = $operation->findSelection($this, $selection);
                $noSelectStatements = false;

                if($selectionInstance === null) return false;
            });

        if($selectionInstance !== null) return $selectionInstance;

        if($noSelectStatements && !$explicit)
        {
            $selectAll = new Selection($this->getTableRef(), '*');

            if(Selection::doesSelect($this, $selectAll, $selection)) return $selectAll;

            return null;
        }

        return null;
    }

    private function findAllSelections(): array
    { 
        $allSelections = [];

        $this->forEachOperations(true, function($operation) use(&$allSelections)
            {
                if(!($operation instanceof SelectOperation)) return;

                $allSelections = array_merge($allSelections, $operation->getSelections());
            });

        return $allSelections;
    }

    private function hasSelection($selection, $explicit): bool
    { 
        return $this->findSelection($selection, $explicit) !== null;
    }

    private function hasSelectionAs($selection, $alias, bool $explicit=false): bool
    { 
        $selection = Selection::create($selection);
        $foundSelection = $this->findSelection($selection, $explicit);

        if($foundSelection === null) return false;

        if($foundSelection->getColumn() === '*') return $selection->getColumn() === $alias;

        return $foundSelection->getName() === $alias;
    }

    public function traverse($modelClass, $traverser=null)
    {
        if($traverser === null)
        {
            $traverser = $modelClass;
            $modelClass = null;
        }

        return $this->runAfter(function($result) use($modelClass, $traverser)
        {
            $resultModelClass = $this->getResultModelClass();

            $resultModelClass::traverse($modelClass, $result, $traverser);

            return $result;
        });
    }

    public function page(int $page, int $pageSize): self
    {
        if($page < 0) throw new \Exception('Page must be >= 0');
        if($pageSize < 0) throw new \Exception('Page size must be >= 0');

        return $this->range($page * $pageSize, ($page + 1) * $pageSize - 1);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): self
    {
        $table = $args[0]['table'] ?? $this->getTableName();

        $iQueryBuilder = $this->getQueryBuilder();

        $tableParts = explode('.', $table);

        $internalOptions = $this->getInternalOptions();

        $columnInfoQuery = $iQueryBuilder->table(end($tableParts))->columnInfo();
        $schema = $internalOptions['schema'] ?? null;

        if($schema === null && count($tableParts) > 1) $schema = $tableParts[0];

        if($schema !== null) $columnInfoQuery->withSchema($schema);

        if($internalOptions['debug'] ?? false) $columnInfoQuery->debug(true);

        return $columnInfoQuery;
    }

    public function withSchema($schema): self
    {
        $internalOptions = $this->getInternalOptions();
        $internalOptions['schema'] = $schema;
        $this->setInternalOptions($internalOptions);

        $context = $this->getInternalContext();

        $context->addOnBuildCallback(function($iBuilder) use($schema)
            {
                if(!$iBuilder->has('/withSchema/'))
                {
                    $iBuilder->addOperationToFront(new QueryBuilderOperation('withSchema'), [$schema]);
                }
            });

        return $this;
    }

    public function debug /* istanbul ignore next */(bool $doIt=true): self
    {
        $internalOptions = $this->getInternalOptions();
        $internalOptions['debug'] = $doIt;
        $this->setInternalOptions($internalOptions);

        $context = $this->getInternalContext();

        $context->addOnBuildCallback(function($iBuilder) use($doIt)
        {
            $iBuilder->addOperation(new QueryBuilderOperation('debug'), [$doIt]);
        });

        return $this;
    }

    private static function writeOperation(ModelQueryBuilder $iBuilder, \Closure $callback): self
    {
        if(!$iBuilder->isFind())
        {
            throw new \Exception('Double call to a write method. You can only call one of the write methods (insert, update, patch, delete, relate, unrelate, increment, decrement) and only once per query builder.');
        }

        $callback();

        return $iBuilder;
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @return ModelQueryBuilder
     */
    public function insert($modelsOrObjects): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects)
        {
            $insertOperation = self::insertOperationFactory($this);

            $this->addOperation($insertOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @return ModelQueryBuilder
     */
    public function insertAndFetch($modelsOrObjects): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects)
        {
            $insertOperation = self::insertOperationFactory($this);

            $insertAndFetchOperation = new InsertAndFetchOperation('insertAndFetch', ['delegate'=>$insertOperation]);

            $this->addOperation($insertAndFetchOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @param array $opt
     * @return ModelQueryBuilder
     */
    public function insertGraph($modelsOrObjects, $opt): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects, $opt)
        {
            $insertOperation = self::insertOperationFactory($this);

            $insertGraphOperation = new InsertGraphOperation('insertGraph', ['delegate'=>$insertOperation, 'options'=>$opt]);

            $this->addOperation($insertGraphOperation, [$modelsOrObjects]);
        });
    }

    /**
     * @param array<int, Model>|array<int, array> $modelsOrObjects
     * @param array $opt
     * @return ModelQueryBuilder
     */
    public function insertGraphAndFetch($modelsOrObjects, $opt): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects, $opt)
        {
            $insertOperation = self::insertOperationFactory($this);

            $insertGraphOperation = new InsertGraphOperation('insertGraph', ['delegate'=>$insertOperation, 'options'=>$opt]);

            $insertGraphAndFetchOperation = new InsertGraphAndFetchOperation('insertGraphAndFetch', ['delegate'=>$insertGraphOperation]);

            $this->addOperation($insertGraphAndFetchOperation, [$modelsOrObjects]);
        });
    }

    public function update($modelOrObject): self
    {
        return self::writeOperation($this, function() use($modelOrObject)
        {
            $updateOperation = self::updateOperationFactory($this);

            $this->addOperation($updateOperation, [$modelOrObject]);
        });
    }

    public function updateAndFetch($modelOrObject): self
    {
        return self::writeOperation($this, function() use($modelOrObject)
        {
            $updateOperation = self::updateOperationFactory($this);
            
            $modelClass = $this->getModelClass();
            if(!($updateOperation instanceof $modelClass)) throw new \Exception('updateAndFetch can only be called for instance operations');

            $updateAndFetchOperation = new UpdateAndFetchOperation('updateAndFetch', ['delegate'=>$updateOperation]);

            $this->addOperation($updateAndFetchOperation, [$modelOrObject]);
        });
    }

    public function updateAndFetchById($id, $modelOrObject): self
    {
        return self::writeOperation($this, function() use($id, $modelOrObject)
        {
            $updateOperation = self::updateOperationFactory($this);
            
            $updateAndFetchOperation = new UpdateAndFetchOperation('updateAndFetch', ['delegate'=>$updateOperation]);

            $this->addOperation($updateAndFetchOperation, [$id, $modelOrObject]);
        });
    }

    public function upsertGraph($modelsOrObjects, $upsertOptions): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects, $upsertOptions)
        {
            $upsertOperation = new UpsertGraphOperation('upsertGraph', ['upsertOptions'=>$upsertOptions]);

            $this->addOperation($upsertOperation, [$modelsOrObjects]);
        });
    }

    public function upsertGraphAndFetch($modelsOrObjects, $upsertOptions): self
    {
        return self::writeOperation($this, function() use($modelsOrObjects, $upsertOptions)
        {
            $upsertOperation = new UpsertGraphOperation('upsertGraph', ['upsertOptions'=>$upsertOptions]);

            $upsertAndFetchOperation = new UpsertGraphAndFetchOperation('upsertGraphAndFetch', ['delegate'=>$upsertOperation]);

            $this->addOperation($upsertAndFetchOperation, [$modelsOrObjects]);
        });
    }

    public function patch($modelOrObject): self
    {
        return self::writeOperation($this, function() use($modelOrObject)
        {
            $patchOperation = self::patchOperationFactory($this);

            $this->addOperation($patchOperation, [$modelOrObject]);
        });
    }

    public function patchAndFetch($modelOrObject): self
    {
        return self::writeOperation($this, function() use($modelOrObject)
        {
            $patchOperation = self::patchOperationFactory($this);

            $modelClass = $this->getModelClass();
            if(!($patchOperation instanceof $modelClass)) throw new \Exception('patchAndFetch can only be called for instance operations');

            $patchAndFetchOperation = new UpdateAndFetchOperation('patchAndFetch', ['delegate'=>$patchOperation]);

            // patchOperation is an instance update operation that already adds the
            // required "where id = $" clause.
            $patchAndFetchOperation->skipIdWhere(true);

            $this->addOperation($patchAndFetchOperation, [$patchOperation->getInstance()->getID(), $modelOrObject]);
        });
    }

    public function patchAndFetchById($id, $modelOrObject): self
    {
        return self::writeOperation($this, function() use($id, $modelOrObject)
        {
            $patchOperation = self::patchOperationFactory($this);

            $patchAndFetchOperation = new UpdateAndFetchOperation('patchAndFetch', ['delegate'=>$patchOperation]);

            $this->addOperation($patchAndFetchOperation, [$id, $modelOrObject]);
        });
    }

    public function delete(...$args): self
    {
        return self::writeOperation($this, function() use($args)
        {
            if(count($args) !== 0) throw new \Exception("Don't pass arguments to delete(). You should use it like this: delete()->where('foo', 'bar')->andWhere(...)");

            $deleteOperation = self::deleteOperationFactory($this);

            $this->addOperation($deleteOperation, $args);
        });
    }

    public function del(...$args): self
    {
        return $this->delete(...$args);
    }

    public function relate(...$args): self
    {
        return self::writeOperation($this, function() use($args)
        {
            $relateOperation = self::relateOperationFactory($this);

            $this->addOperation($relateOperation, $args);
        });
    }

    public function unrelate(...$args): self
    {
        return self::writeOperation($this, function() use($args)
        {
            if(count($args) !== 0) throw new \Exception("Don't pass arguments to unrelate(). You should use it like this: unrelate()->where('foo', 'bar')->andWhere(...)");

            $unrelateOperation = self::unrelateOperationFactory($this);

            $this->addOperation($unrelateOperation, $args);
        });
    }

    public function increment($propertyName, $howMuch): self
    {
        $modelClass = $this->getModelClass();
        $columnName = $modelClass::propertyNameToColumnName($propertyName);

        return $this->patch([$columnName=>new Raw('?? + ?', $columnName, $howMuch)]);
    }

    public function decrement($propertyName, $howMuch): self
    {
        $modelClass = $this->getModelClass();
        $columnName = $modelClass::propertyNameToColumnName($propertyName);

        return $this->patch([$columnName=>new Raw('?? - ?', $columnName, $howMuch)]);
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

    private static function ensureJoinRelatedOperation(self $iBuilder, string $joinOperation): ModelQueryBuilderOperation
    {
        $operationName = $joinOperation.'Related';

        $operation = $iBuilder->findOperation($operationName);

        if($operation === null)
        {
            $operation = new JoinRelatedOperation($operationName, ['joinOperation'=>$joinOperation]);
            $iBuilder->addOperation($operation);
        }

        return $operation;
    }

    public function joinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'innerJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function innerJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'innerJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function outerJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'outerJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function fullOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'fullOuterJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function leftJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'leftJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function leftOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'leftOuterJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function rightJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'rightJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

        return $this;
    }

    public function rightOuterJoinRelated($expression, $options): self
    {
        self::ensureJoinRelatedOperation($this, 'rightOuterJoin')->addCall(['expression'=>$expression, 'options'=>$options]);

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

    public function onBuildQueryBuilder(...$args): self
    {
        return $this->addOperation(new OnBuildQueryBuilderOperation('onBuildQueryBuilder'), $args);
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

    public function for($relatedQueryFor=null): self
    {
        if($relatedQueryFor === null) return $this->relatedQueryFor;
        
        $this->relatedQueryFor = $relatedQueryFor;

        return $this;
    }

    /**
     * 2023-07-10
     * @return FindOperation
     */
    private static function findOperationFactory(): FindOperation
    {
        return new FindOperation('find');
    }

    /**
     * 2023-07-10
     * @return InsertOperation
     */
    private static function insertOperationFactory(): InsertOperation
    {
        return new InsertOperation('insert');
    }

    /**
     * 2023-07-10
     * @return UpdateOperation
     */
    private static function updateOperationFactory(): UpdateOperation
    {
        return new UpdateOperation('update');
    }

    /**
     * 2023-07-10
     * @return UpdateOperation
     */
    private static function patchOperationFactory(): UpdateOperation
    {
        return new UpdateOperation('patch');
    }

    /**
     * 2023-07-10
     * @return RelateOperation
     */
    private static function relateOperationFactory(): RelateOperation
    {
        return new RelateOperation('relate');
    }

    /**
     * 2023-07-10
     * @return UnrelateOperation
     */
    private static function unrelateOperationFactory(): UnrelateOperation
    {
        return new UnrelateOperation('unrelate');
    }

    /**
     * 2023-07-10
     * @return DeleteOperation
     */
    private static function deleteOperationFactory(): DeleteOperation
    {
        return new DeleteOperation('delete');
    }
}
