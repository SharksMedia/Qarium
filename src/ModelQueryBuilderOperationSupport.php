<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Closure;
use Sharksmedia\Objection\Operations\ModelQueryBuilderOperation;
use Sharksmedia\QueryBuilder\Query;
use Sharksmedia\QueryBuilder\QueryBuilder;

abstract class ModelQueryBuilderOperationSupport
{
    public const ALL_SELECTOR = true;
    public const QUERY_BUILDER_CONTEXT = ModelQueryBuilderContextBase::class;
    public const QUERY_BUILDER_USER_CONTEXT = ModelQueryBuilderContextBase::class;
    public const SELECT_SELECTOR = '/^(select|columns|column|distinct|count|countDistinct|min|max|sum|sumDistinct|avg|avgDistinct)$/';
    public const WHERE_SELECTOR = '/^(where|orWhere|andWhere|find\w+)/';
    public const ON_SELECTOR = '/^(on|orOn|andOn)/';
    public const JOIN_SELECTOR = '/orderBy/';
    public const FROM_SELECTOR = '/(join|joinRaw|joinRelated)$/i';
    public const ORDER_BY_SELECTOR = '/^(from|into|table)$/';

    /**
     * @var class-string<Model>
     */
    protected $modelClass;

    /**
     * @var ModelQueryBuilderOperation[]
     */
    protected $operations;

    /**
     * @var ModelQueryBuilderContextBase|ModelQueryBuilderContextUser
     */
    protected $context;

    /**
     * @var ModelQueryBuilderOperationSupport
     */
    protected $parentQuery;

    /**
     * @var bool
     */
    protected $isPartialQuery;

    /**
     * @var ModelQueryBuilderOperation[]
     */
    protected $activeOperations;


    /**
     * 2023-07-07
     * @param array<int, mixed> $args
     */
    public function __construct(...$args)
    {
        self::init($this, ...$args);
    }

    /**
     * 2023-07-07
     * @param class-string<Model> $modelClass
     */
    private static function init(ModelQueryBuilderOperationSupport $instance, $modelClass): void
    {
        $instance->modelClass = $modelClass;
        $instance->operations = [];

        $queryBuilderContextClass = self::getModelQueryBuilderContextClass();

        $instance->context = new $queryBuilderContextClass($instance);
        $instance->parentQuery = null;
        $instance->isPartialQuery = false;
        $instance->activeOperations = [];
    }

    /**
     * 2023-07-07
     * @return class-string<ModelQueryBuilderContextBase>
     */
    public static function getModelQueryBuilderContextClass(): string
    {
        return ModelQueryBuilderContext::class;
    }

    /**
     * 2023-07-07
     * @return class-string<ModelQueryBuilderContextBase>
     */
    public static function getModelQueryBuilderUserContextClass(): string
    {
        return ModelQueryBuilderContextUser::class;
    }

    /**
     * 2023-07-07
     * @param class-string<Model> $modelClass
     * @return ModelQueryBuilderOperationSupport|ModelQueryBuilderBase|ModelQueryBuilder
     */
    public static function forClass(string $modelClass)
    {
        return new static($modelClass);
    }

    /**
     * 2023-07-07
     * @return class-string<Model>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
    /**
     * @param mixed $obj
     * @return ModelQueryBuilderContextBase|ModelQueryBuilderContextUser
     */
    public function getContext($obj=null)
    {
        return $this->context;
    }

    /**
     * 2023-07-07
     * @return ModelQueryBuilderContextBase $context
     * @param mixed $context
     */
    public function setContext($context): ModelQueryBuilderOperationSupport
    {
        $this->context->userContext = $this->context->userContext->newMerge($this, $context);

        return $this;
    }

    public function clearContext(): static
    {
        $context = $this->context;
        $userContextClass = static::QUERY_BUILDER_USER_CONTEXT;
        $context->userContext = new $userContextClass($this);

        return $this;
    }

    public function getInternalContext(): ModelQueryBuilderContext
    {
        return $this->context;
    }
    /**
     * @param mixed $context
     */
    public function setInternalContext($context): ModelQueryBuilderOperationSupport
    {
        $this->context = $context;

        return $this;
    }

    public function getInternalOptions(): ?array
    {
        return $this->context->options ?? null;
    }
    /**
     * @param array<int,mixed> $options
     */
    public function setInternalOptions(array $options): ModelQueryBuilderOperationSupport
    {
        $this->context->options = array_merge($this->context->options, $options);

        return $this;
    }

    public function getIsPartial(): bool
    {
        return $this->isPartialQuery;
    }

    public function setIsPartial(bool $isPartial): static
    {
        $this->isPartialQuery = $isPartial;

        return $this;
    }

    public function isInternal(): bool
    {
        $internalOptions = $this->getInternalOptions();

        return $internalOptions['isInternalQuery'];
    }

    public function setTableNameFor(string $tableName, string $newTableName): static
    {
        $context = $this->getInternalContext();

        $context->tableMap[$tableName] = $newTableName;

        return $this;
    }

    public function getTableNameFor(string $tableName): string
    {
        $context = $this->getInternalContext();

        return $context->tableMap[$tableName] ?? $tableName;
    }

    public function setAliasFor(string $tableName, string $alias): static
    {
        $context = $this->getInternalContext();

        $context->aliasMap[$tableName] = $alias;

        return $this;
    }

    public function getAliasFor(string $tableName): ?string
    {
        $context = $this->getInternalContext();

        return $context->aliasMap[$tableName] ?? null;
    }

    public function getTableRefFor(string $tableName): string
    {
        return $this->getAliasFor($tableName) ?? $this->getTableNameFor($tableName);
    }

    public function childQueryOf(ModelQueryBuilderOperationSupport $query, bool $isFork=false, bool $isInternalQuery=false): static
    {
        $currentContext = $this->getContext();
        $queryContext = $query->getContext();

        if($isFork) $queryContext = clone $queryContext;

        if($isInternalQuery) $queryContext->options['isInternalQuery'] = true;

        $this->parentQuery = $query;
        $this->setInternalContext($queryContext);
        $this->setContext($currentContext);

        // FIXME: FIgure what should be done about unsafe knex queries
        // Use the parent's shark-query if there was no shark-query in `context`.
        // if($this->getUnsafeQuery() === null) {
        //     $this->setUnsafeQuery($query->getUnsafeQuery());
        // }
        
        return $this;
    }

    public function subQueryOf(ModelQueryBuilderOperationSupport $query): static
    {
        if($this->isInternal())
        {
            $context = $this->getInternalContext();

            $context->aliasMap = array_merge($query->getInternalContext()->aliasMap, $context->aliasMap);
            $context->tableMap = array_merge($query->getInternalContext()->tableMap, $context->tableMap);
        }

        $this->parentQuery = $query;

        // FIXME: FIgure what should be done about unsafe knex queries
        // Use the parent's shark-query if there was no shark-query in `context`.
        // if($this->getUnsafeQuery() === null) {
        //     $this->setUnsafeQuery($query->getUnsafeQuery());
        // }

        return $this;
    }

    public function getParentQuery(): ?ModelQueryBuilderOperationSupport
    {
        return $this->parentQuery;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        $iQueryBuilder = $this->getUnsafeQueryBuilder();

        if($iQueryBuilder === null) throw new \Exception("no database connection available for a query. You need to bind the model class or the query to a shark builder instance.");

        return $iQueryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $iQueryBuilder): static
    {
        $this->context->iQueryBuilder = $iQueryBuilder;

        return $this;
    }

    public function getUnsafeQueryBuilder(): ?QueryBuilder
    {
        return $this->context->iQueryBuilder ?? $this->modelClass::getQueryBuilder() ?? null;
    }
    /**
     * @param mixed $operationSelector
     * @return ModelQueryBuilderOperationSupport
     */
    public function clear($operationSelector): static
    {
        $operationsToRemove = [];

        $callback = function($operation) use(&$operationsToRemove)
        {
            if($operation->isAncestorInSet($operationsToRemove)) $operationsToRemove[] = $operation;
        };

        $this->forEachOperations($operationSelector, $callback);

        foreach($operationsToRemove as $operation) $this->removeOperation($operation);

        return $this;

    }
    /**
     * @return ModelQueryBuilderOperationSupport
     */
    public function toFindQuery(): static
    {
        $findQuery = clone $this;
        $operationsToReplace = [];
        $operationsToRemove = [];

        $operationSelectorCallback = function($operation) { return $operation->hasToFindOperation(); };
        $callback = function($operation) use(&$findQuery, &$operationsToReplace, &$operationsToRemove)
        {
            $findOperation = $operation->toFindOperation($findQuery);

            if(!$findOperation)
            {
                $operationsToRemove[] = $operation;
                return;
            }

            $operationsToReplace[] = ['operation'=>$operation, 'findOperation'=>$findOperation];
        };

        $findQuery->forEachOperations($operationSelectorCallback, $callback);

        foreach($operationsToRemove as $operation) $findQuery->removeOperation($operation);
        foreach($operationsToReplace as $operation) $findQuery->replaceOperation($operation['operation'], $operation['findOperation']);

        return $findQuery;
    }

    public function clearSelect(): static
    {
        return $this->clear(self::SELECT_SELECTOR);
    }

    public function clearWhere(): static
    {
        return $this->clear(self::WHERE_SELECTOR);
    }

    public function clearOrder(): static
    {
        return $this->clear(self::ORDER_BY_SELECTOR);
    }
    /**
     * @param Closure(): void $operationSelector
     * @return ModelQueryBuilderOperationSupport
     */
    public function copyFrom(ModelQueryBuilderOperationSupport $iBuilder, \Closure $operationSelector): static
    {
        $operationsToAdd = [];

        $callback = function($operation) use(&$operationsToAdd)
        {
            // If an ancestor operation has already been added, there is no need to add
            // FIXME: Set the key in operationsToAdd array
            if(!$operation->isAncestorInSet($operationsToAdd)) $operationsToAdd[] = $operation;
        };

        $iBuilder->forEachOperations($operationSelector, $callback);

        foreach($operationsToAdd as $operation)
        {
            $operationClone = clone $operation;

            // We may be moving nested operations to the root. Clear any links to the parent operations.
            $operationClone->parentOperation = null;
            $operationClone->adderHookName = null;

            // We don't use `addOperation` here because we don't what to call `onAdd` or add these operations as child operations.
            $this->operations[] = $operationClone;
        }

        return $this;
    }
    /**
     * @param Closure(): void $operatorSelector
     */
    public function has($operatorSelector): bool
    {
        return $this->findOperation($operatorSelector) !== null;
    }
    /**
     * @param mixed $operationSelector
     * @param Closure(): void|string $callback
     */
    public function forEachOperations($operationSelector, \Closure $callback, bool $match=true): ModelQueryBuilderOperationSupport
    {
        $selector = self::buildFunctionForOperationSelector($operationSelector);

        foreach($this->operations as $operation)
        {
            if($selector($operation) === $match && $callback($operation) === false) break;

            $childRes = $operation->forEachDescendantOperation(function($operation) use(&$selector, &$callback, &$match)
            {
                if($selector($operation) === $match && $callback($operation) === false) return false;
            });

            if($childRes === false) break;
        }

        return $this;
    }
    /**
     * @param Closure(): void|string $operationSelector
     */
    public function findOperation($operationSelector): ?ModelQueryBuilderOperation
    {
        $operation = null;

        $this->forEachOperations($operationSelector, function($op) use(&$operation)
        {
            $operation = $op;
            return false;
        });

        return $operation;
    }
    /**
     * @param Closure(): void $operationSelector
     */
    public function findLastOperation($operationSelector): ?ModelQueryBuilderOperationSupport
    {
        $operation = null;

        $this->forEachOperations($operationSelector, function($op) use(&$operation)
        {
            $operation = $op;
        });

        return $operation;
    }
    /**
     * @param Closure(): void $operationSelector
     */
    public function everyOperation(\Closure $operationSelector): bool
    {
        $every = true;

        $this->forEachOperations($operationSelector, function($operation) use(&$every)
        {
            $every = false;
            return false;
        }, false);

        return $every;
    }
    /**
     * @param mixed $operation
     * @param mixed $hookName
     * @param mixed $args
     */
    public function callOperationMethod($operation, $hookName, $args)
    {
        try
        {
            $operation->removeChildOperationByHookName($hookName);

            $this->activeOperations[] =
            [
                'operation' => $operation,
                'hookName' => $hookName,
            ];

            return $operation->$hookName($this, ...$args);
        }
        finally
        {
            array_pop($this->activeOperations);
        }
    }
    /**
     * @param mixed $args
     */
    public function addOperation(ModelQueryBuilderOperation $operation, $args): static
    {
        return $this->addOperationUsingMethod('push', $operation, $args);
    }
    /**
     * @param mixed $args
     */
    public function addOperationToFront(ModelQueryBuilderOperation $operation, $args): ModelQueryBuilderOperationSupport
    {
        return $this->addOperationUsingMethod('unshift', $operation, $args);
    }
    /**
     * @param array<int,mixed> $args
     */
    public function addOperationUsingMethod(string $method, ModelQueryBuilderOperation $operation, array $args): ModelQueryBuilderOperationSupport
    {
        $shouldAdd = $this->callOperationMethod($operation, 'onAdd', $args);

        if(!$shouldAdd) return $this;

        if(count($this->activeOperations) !== 0)
        {
            $lastActiveOperation = end($this->activeOperations);
            $parentOperation = $lastActiveOperation['operation'];
            $hookName = $lastActiveOperation['hookName'];

            $parentOperation->addChildOperation($operation, $hookName);

            return $this;
        }

        if($method === 'push') $this->operations[] = $operation;
        else if($method === 'unshift') array_unshift($this->operations, $operation);
        else throw new \Exception("Invalid method '$method'");

        return $this;
    }

    public function removeOperation(ModelQueryBuilderOperation $operation): static
    {
        if($operation->getParentOperation() !== null)
        {
            $operation->getParentOperation()->removeChildOperation($operation);
            return $this;
        }

        $index = array_search($operation, $this->operations, true);

        if($index === false) return $this;

        array_splice($this->operations, $index, 1);

        return $this;
    }

    public function replaceOperation(ModelQueryBuilderOperation $operation, ModelQueryBuilderOperation $newOperation): static
    {
        if($operation->getParentOperation() !== null)
        {
            $operation->getParentOperation()->replaceChildOperation($operation, $newOperation);
            return $this;
        }

        $index = array_search($operation, $this->operations, true);

        if($index === false) return $this;

        $this->operations[$index] = $newOperation;

        return $this;
    }

    public function toQueryBuilder(?QueryBuilder $iQueryBuilder=null): QueryBuilder
    {
        $iQueryBuilder = $iQueryBuilder ?? $this->getQueryBuilder();

        $this->executeOnBuild();

        return $this->executeOnBuildQueryBuilder($iQueryBuilder);
    }

    public function executeOnBuild(): void
    {
        $this->forEachOperations(function(){ return true; }, function($operation)
        {
            if($operation->hasOnBuild()) $this->callOperationMethod($operation, 'onBuild', [$this]);
        });
    }

    public function executeOnBuildQueryBuilder(QueryBuilder $iQueryBuilder): QueryBuilder
    {
        $this->forEachOperations(function(){ return true; }, function($operation) use(&$iQueryBuilder)
        {
            if($operation->hasOnBuildQueryBuilder())
            {
                $iNewQueryBuilder = $this->callOperationMethod($operation, 'onBuildQueryBuilder', [$iQueryBuilder, $this]);

                $iQueryBuilder = $iNewQueryBuilder ?? $iQueryBuilder;
            }
        });

        return $iQueryBuilder;
    }

    public function toString(): string
    {
        return $this->toQuery()->getSQL();
    }

    public function toQuery(): Query
    {
        return $this->toQueryBuilder()->toSQL();
    }
    /**
     * @param mixed $operationSelector
     * @return callable
     */
    private static function buildFunctionForOperationSelector($operationSelector): callable
    {
        if($operationSelector === true) return function(){ return true; };
        else if($operationSelector === false) return function(){ return false; };
        else if(is_string($operationSelector) && preg_match('/^\/.+\/[a-z]+?$/', $operationSelector) === 1)
        {
            return function($operation) use(&$operationSelector)
            {
                return preg_match($operationSelector, $operation->getName()) === 1;
            };
        }
        else if(is_string($operationSelector))
        {
            return function($operation) use(&$operationSelector)
            {
                return $operation instanceof $operationSelector;
            };
        }
        else if(is_array($operationSelector))
        {
            return function($operation) use(&$operationSelector)
            {
                foreach($operationSelector as $selector)
                {
                    if($selector($operation)) return true;
                }

                return false;
            };
        }
        else if(is_callable($operationSelector)) return $operationSelector;
        else throw new \Exception("Invalid operation selector");
    }
}
