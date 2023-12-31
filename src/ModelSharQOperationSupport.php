<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Closure;
use Sharksmedia\Qarium\Operations\ModelSharQOperation;
use Sharksmedia\SharQ\Query;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Join;

abstract class ModelSharQOperationSupport
{
    public const QUERY_BUILDER_CONTEXT      = ModelSharQContextBase::class;
    public const QUERY_BUILDER_USER_CONTEXT = ModelSharQContextUser::class;

    public const ALL_SELECTOR      = true;
    public const SELECT_SELECTOR   = '/^(select|columns|column|distinct|count|countDistinct|min|max|sum|sumDistinct|avg|avgDistinct)$/';
    public const WHERE_SELECTOR    = '/^(where|orWhere|andWhere|find\w+)/';
    public const ON_SELECTOR       = '/^(on|orOn|andOn)/';
    public const ORDER_BY_SELECTOR = '/^orderBy/';
    public const JOIN_SELECTOR     = '/(join|joinRaw|joinRelated)$/';
    public const FROM_SELECTOR     = '/^(from|into|table)$/';
    public const LIMIT_SELECTOR    = '/^(limit|offset)$/';

    /**
     * @var class-string<Model>
     */
    protected $modelClass;

    /**
     * @var ModelSharQOperation[]
     */
    public $operations;

    /**
     * @var ModelSharQContextBase|ModelSharQContextUser
     */
    protected $context;

    /**
     * @var ModelSharQOperationSupport
     */
    protected $parentQuery;

    /**
     * @var bool
     */
    protected $isPartialQuery;

    /**
     * @var ModelSharQOperation[]
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
    private static function init(ModelSharQOperationSupport $instance, $modelClass): void
    {
        $instance->modelClass = $modelClass;
        $instance->operations = [];

        $queryBuilderContextClass = static::getModelSharQContextClass();

        $instance->context          = new $queryBuilderContextClass($instance);
        $instance->parentQuery      = null;
        $instance->isPartialQuery   = false;
        $instance->activeOperations = [];
    }

    /**
     * 2023-07-07
     * @return class-string<ModelSharQContextBase>
     */
    public static function getModelSharQContextClass(): string
    {
        return ModelSharQContext::class;
    }

    /**
     * 2023-07-07
     * @return class-string<ModelSharQContextBase>
     */
    public static function getModelSharQUserContextClass(): string
    {
        return ModelSharQContextUser::class;
    }

    /**
     * 2023-07-07
     * @param class-string<Model> $modelClass
     * @return ModelSharQOperationSupport|ModelSharQ
     */
    public static function forClass(string $modelClass): self
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
     * @return ModelSharQContextBase|ModelSharQContextUser
     */
    public function getContext($obj = null)
    {
        return $this->context;
    }

    /**
     * 2023-07-07
     * @param mixed $context
     */
    public function setContext($context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getUserContext(): ModelSharQContextUser
    {
        return $this->getContext()->userContext;
    }

    /**
     * 2023-07-07
     * @param mixed $obj
     */
    public function setUserContext($obj): self
    {
        $this->context->userContext = $this->context->userContext->newMerge($this, $obj);

        return $this;
    }

    public function context($obj = null)
    {
        if ($obj === null)
        {
            return $this->getUserContext();
        }

        $this->setUserContext($obj);

        return $this;
    }

    public function clearContext(): self
    {
        $context              = $this->context;
        $userContextClass     = static::QUERY_BUILDER_USER_CONTEXT;
        $context->userContext = new $userContextClass($this);

        return $this;
    }

    public function getInternalContext(): ModelSharQContext
    {
        return $this->context;
    }
    /**
     * @param mixed $context
     */
    public function setInternalContext($context): ModelSharQOperationSupport
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
    public function setInternalOptions(array $options): ModelSharQOperationSupport
    {
        $this->context->options = array_merge($this->context->options ?? [], $options);

        return $this;
    }

    public function getIsPartial(): bool
    {
        return $this->isPartialQuery;
    }

    public function setIsPartial(bool $isPartial): self
    {
        $this->isPartialQuery = $isPartial;

        return $this;
    }

    public function isInternal(): bool
    {
        $internalOptions = $this->getInternalOptions();

        return $internalOptions['isInternalQuery'] ?? false;
    }

    public function setTableNameFor(string $tableName, string $newTableName): self
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

    public function setAliasFor(string $tableName, string $alias): self
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

    public function childQueryOf(ModelSharQOperationSupport $query, bool $isFork = false, bool $isInternalQuery = false): self
    {
        $currentContext = $this->context();
        $queryContext   = $query->getContext();

        if ($isFork)
        {
            $queryContext = clone $queryContext;
        }

        if ($isInternalQuery)
        {
            $options = $this->getInternalOptions();

            $options['isInternalQuery'] = true;

            $this->setInternalOptions($options);
        }

        $this->parentQuery = $query;
        $this->setContext($queryContext);
        $this->context($currentContext);
        
        return $this;
    }

    public function subQueryOf(ModelSharQOperationSupport $query): self
    {
        if ($this->isInternal())
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

    public function getParentQuery(): ?ModelSharQOperationSupport
    {
        return $this->parentQuery;
    }

    public function getSharQ(): SharQ
    {
        $iSharQ = $this->getUnsafeSharQ();

        if ($iSharQ === null)
        {
            throw new \Exception("no database connection available for a query. You need to bind the model class or the query to a shark builder instance.");
        }

        return $iSharQ;
    }

    public function setSharQ(SharQ $iSharQ): self
    {
        $this->context->iSharQ = $iSharQ;

        return $this;
    }

    public function getUnsafeSharQ(): ?SharQ
    {
        $iSharQ = $this->context->iSharQ ?? $this->modelClass::getSharQ() ?? null;

        if ($iSharQ === null)
        {
            return null;
        }

        return clone $iSharQ;
    }
    /**
     * @param mixed $operationSelector
     * @return ModelSharQOperationSupport
     */
    public function clear($operationSelector): self
    {
        $operationsToRemove = [];

        $callback = function(ModelSharQOperation $operation, $selectorResult) use (&$operationsToRemove, $operationSelector)
        {
            if ($selectorResult && !$operation->isAncestorInSet($operationsToRemove))
            {
                $operationsToRemove[] = $operation;
            }

            return null;
        };

        $this->forEachOperations($operationSelector, $callback);

        foreach ($operationsToRemove as $operation)
        {
            $this->removeOperation($operation);
        }

        return $this;
    }
    /**
     * @return ModelSharQOperationSupport
     */
    public function toFindQuery(): self
    {
        $findQuery           = clone $this;
        $operationsToReplace = [];
        $operationsToRemove  = [];

        $operationSelectorCallback = function($operation)
        {
            return $operation->hasToFindOperation();
        };

        $callback = function($operation) use (&$findQuery, &$operationsToReplace, &$operationsToRemove)
        {
            $findOperation = $operation->toFindOperation($findQuery);

            if (!$findOperation)
            {
                $operationsToRemove[] = $operation;

                return null;
            }

            $operationsToReplace[] = ['operation' => $operation, 'findOperation' => $findOperation];

            return null;
        };

        $findQuery->forEachOperations($operationSelectorCallback, $callback);

        foreach ($operationsToRemove as $operation)
        {
            $findQuery->removeOperation($operation);
        }

        foreach ($operationsToReplace as $operation)
        {
            $findQuery->replaceOperation($operation['operation'], $operation['findOperation']);
        }

        return $findQuery;
    }

    public function clearSelect(): self
    {
        return $this->clear(self::SELECT_SELECTOR);
    }

    public function clearWhere(): self
    {
        return $this->clear(self::WHERE_SELECTOR);
    }

    public function clearOrder(): self
    {
        return $this->clear(self::ORDER_BY_SELECTOR);
    }
    /**
     * @param Closure(): void $operationSelector
     * @return ModelSharQOperationSupport
     */
    public function copyFrom(ModelSharQOperationSupport $iBuilder, $operationSelector, bool $debug = false): self
    {
        $operationsToAdd = [];

        $callback = function(ModelSharQOperation $operation, $selectorResult) use (&$operationsToAdd, $debug)
        {
            // If an ancestor operation has already been added, there is no need to add
            if ($selectorResult && $operation->isAncestorInSet($operationsToAdd) === false)
            {
                $operationsToAdd[] = $operation;
            }

            return null;
        };

        $iBuilder->forEachOperations($operationSelector, $callback);

        foreach ($operationsToAdd as $operation)
        {
            $operationClone = clone $operation;

            // We may be moving nested operations to the root. Clear any links to the parent operations.
            $operationClone->setParentOperation(null);
            $operationClone->setAdderHookName(null);

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
    public function forEachOperations($operationSelector, \Closure $callback, bool $match = true): ModelSharQOperationSupport
    {
        $selector = self::buildFunctionForOperationSelector($operationSelector);

        foreach ($this->operations as $operation)
        {
            $selectorResult = $selector($operation);
            $callbackResult = $callback($operation, $selectorResult);

            if ($selectorResult === $match && $callbackResult === false)
            {
                break;
            }

            $childRes = $operation->forEachDescendantOperation(function($operation) use (&$selector, &$callback, &$match, $operationSelector)
            {
                $selectorResult = $selector($operation);
                $callbackResult = $callback($operation, $selectorResult);

                if ($selectorResult === $match && $callbackResult === false)
                {
                    return false;
                }

                return true;
            });

            if ($childRes === false)
            {
                break;
            }
        }

        return $this;
    }
    /**
     * @param Closure(): void|string $operationSelector
     */
    public function findOperation($operationSelector): ?ModelSharQOperation
    {
        $operation = null;

        $this->forEachOperations($operationSelector, function($op, $selectionResult) use (&$operation)
        {
            if ($selectionResult)
            {
                $operation = $op;
            }

            return false;
        });

        return $operation;
    }
    /**
     * @param Closure(): void $operationSelector
     */
    public function findLastOperation($operationSelector): ?ModelSharQOperation
    {
        $operation = null;

        $this->forEachOperations($operationSelector, function($op, $selectorResult) use (&$operation)
        {
            if ($selectorResult)
            {
                $operation = $op;
            }

            return null;
        });

        return $operation;
    }
    /**
     * @param Closure(): void $operationSelector
     */
    public function everyOperation(\Closure $operationSelector): bool
    {
        $every = true;

        $this->forEachOperations($operationSelector, function($operation, $selectorResult) use (&$every)
        {
            if (!$selectorResult)
            {
                $every = false;
            }

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
                'hookName'  => $hookName,
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
    public function addOperation(ModelSharQOperation $operation, $args): self
    {
        return $this->addOperationUsingMethod('push', $operation, $args);
    }
    /**
     * @param mixed $args
     */
    public function addOperationToFront(ModelSharQOperation $operation, $args): ModelSharQOperationSupport
    {
        return $this->addOperationUsingMethod('unshift', $operation, $args);
    }
    /**
     * @param array<int,mixed> $args
     */
    public function addOperationUsingMethod(string $method, ModelSharQOperation $operation, array $args): ModelSharQOperationSupport
    {
        $shouldAdd = $this->callOperationMethod($operation, 'onAdd', $args);

        if (!$shouldAdd)
        {
            return $this;
        }

        if (count($this->activeOperations) !== 0)
        {
            /** @var ModelSharQOperation $lastActiveOperation */
            $lastActiveOperation = end($this->activeOperations);

            /** @var ModelSharQOperation $parentOperation */
            $parentOperation = $lastActiveOperation['operation'];

            /** @var string $hookName */
            $hookName = $lastActiveOperation['hookName'];

            $parentOperation->addChildOperation($hookName, $operation);

            return $this;
        }

        if ($method === 'push')
        {
            $this->operations[] = $operation;
        }
        else if ($method === 'unshift')
        {
            array_unshift($this->operations, $operation);
        }
        else
        {
            throw new \Exception("Invalid method '$method'");
        }

        return $this;
    }

    public function removeOperation(ModelSharQOperation $operation): self
    {
        if ($operation->getParentOperation() !== null)
        {
            $operation->getParentOperation()->removeChildOperation($operation);

            return $this;
        }

        $index = array_search($operation, $this->operations, true);

        if ($index === false)
        {
            return $this;
        }

        array_splice($this->operations, $index, 1);

        return $this;
    }

    public function replaceOperation(ModelSharQOperation $operation, ModelSharQOperation $newOperation): self
    {
        if ($operation->getParentOperation() !== null)
        {
            $operation->getParentOperation()->replaceChildOperation($operation, $newOperation);

            return $this;
        }

        $index = array_search($operation, $this->operations, true);

        if ($index === false)
        {
            return $this;
        }

        $this->operations[$index] = $newOperation;

        return $this;
    }

    /**
     * @param SharQ|Join|null $iSharQ
     * @return SharQ|Join|null
     */
    public function toSharQ($iSharQ = null)
    {
        $iClonedBuilder = clone $this;

        $iSharQ = $iSharQ ?? $iClonedBuilder->getSharQ();

        $iClonedBuilder->executeOnBuild();

        return $iClonedBuilder->executeOnBuildSharQ($iSharQ);
    }

    public function executeOnBuild(): void
    {
        $this->forEachOperations(self::ALL_SELECTOR, function($operation)
        {
            if ($operation->hasOnBuild())
            {
                $this->callOperationMethod($operation, 'onBuild', [$this]);
            }

            return null;
        });
    }

    /**
     * @param SharQ|Join|null $iSharQ
     * @return SharQ|Join|null
     */
    public function executeOnBuildSharQ($iSharQ)
    {
        $this->forEachOperations(self::ALL_SELECTOR, function($operation) use (&$iSharQ)
        {
            if ($operation->hasOnBuildSharQ())
            {
                $iNewSharQ = $this->callOperationMethod($operation, 'onBuildSharQ', [$iSharQ]);

                $iSharQ = $iNewSharQ ?? $iSharQ;
            }

            return null;
        });

        return $iSharQ;
    }

    public function toString(): string
    {
        return $this->toQuery()->getSQL();
    }

    public function toQuery(): Query
    {
        return $this->toSharQ()->toQuery();
    }

    public function toSQL(): string
    {
        return $this->toQuery()->getSQL();
    }

    /**
     * @param mixed $operationSelector
     * @return callable
     */
    private static function buildFunctionForOperationSelector($operationSelector): callable
    {
        if ($operationSelector === true)
        {
            return function()
            { return true; };
        }
        else if ($operationSelector === false)
        {
            return function()
            { return false; };
        }
        else if (is_string($operationSelector) && preg_match('/^\/.+\/$/', $operationSelector) === 1) // Assuming it's a regex if the string starts and ends with a slash
        {
            return function($operation) use (&$operationSelector)
            {
                return preg_match($operationSelector, $operation->getName()) === 1;
            };
        }
        else if (is_string($operationSelector) && preg_match('/\\\\/', $operationSelector) === 1)
        {
            return function($operation) use (&$operationSelector)
            {
                return $operation instanceof $operationSelector;
            };
        }
        else if (is_string($operationSelector))
        {
            return self::buildFunctionForOperationSelector('/^'.$operationSelector.'$/');
        }
        else if (is_array($operationSelector))
        {
            return function($operation) use (&$operationSelector)
            {
                foreach ($operationSelector as $selector)
                {
                    if ($selector($operation))
                    {
                        return true;
                    }
                }

                return false;
            };
        }
        else if ($operationSelector instanceof \Closure)
        {
            return $operationSelector;
        }
        else
        {
            throw new \Exception("Invalid operation selector");
        }
    }
}
