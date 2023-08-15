<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\JoinBuilder;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;

abstract class ModelQueryBuilderOperation
{
    /**
     * 2023-07-04
     * @var string
     */
    protected string $name;

    /**
     * 2023-07-04
     * @var array
     */
    protected array $options;

    /**
     * 2023-07-04
     * From which hook was this operation added as a child operation.
     * @var string|null
     */
    protected ?string $adderHookName;

    /**
     * 2023-07-04
     * The parent operation that added this operation.
     * @var ModelQueryBuilderOperation|null
     */
    protected ?ModelQueryBuilderOperation $parentOperation;

    /**
     * 2023-07-04
     * Operations this operation added in any of its hooks.
     * @var array<int, ModelQueryBuilderOperation>
     */
    protected array $childOperations;

    public string $_identifier;

    /**
     * 2023-07-04
     * @param string $name
     * @param array $options
     */
    public function __construct(string $name, array $options=[])
    {
        $this->name = $name;
        $this->options = $options;

        $this->setAdderHookName(null);
        $this->setParentOperation(null);
        $this->childOperations = [];

        $this->_identifier = spl_object_hash($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected static function funcHasBeenOverriden(string $function): bool
    {
        $reflector = new \ReflectionMethod(static::class, $function);
        $hasOverriden = ($reflector->getDeclaringClass()->getName() === static::class);

        if(static::class === ModelQueryBuilderOperation::class) return false;

        return $hasOverriden;
    }

    /**
     * 2023-07-04
     * @return ModelQueryBuilderOperation|null
     */
    public function getParentOperation(): ?ModelQueryBuilderOperation
    {
        return $this->parentOperation;
    }

    public function setParentOperation(?ModelQueryBuilderOperation $parentOperation): void
    {
        $this->parentOperation = $parentOperation;
    }

    public function setAdderHookName(?string $adderHookName): void
    {
        $this->adderHookName = $adderHookName;
    }

    /**
     * 2023-07-04
     * @param class-string<ModelQueryBuilderOperation> $className
     * @return bool
     */
    public function isOperation(string $className): bool
    {
        return $className instanceof $this;
    }

    public function is(string $className): bool
    {
        return $this->isOperation($className);
    }

    public function hasHook(string $hookName): bool
    {
        $hookNameHashMethodMap =
        [
            'onAdd'=>'hasOnAdd',
            'onBefore1'=>'hasOnBefore1',
            'onBefore2'=>'hasOnBefore2',
            'onBefore3'=>'hasOnBefore3',
            'onBuild'=>'hasOnBuild',
            'onBuildKnex'=>'hasOnBuildKnex',
            'onRawResult'=>'hasOnRawResult',
            'queryExecutor'=>'hasQueryExecutor',
            'onAfter1'=>'hasOnAfter1',
            'onAfter2'=>'hasOnAfter2',
            'onAfter3'=>'hasOnAfter3',
            'onError'=>'hasOnError',
        ];

        $hookNameHashMethod = $hookNameHashMethodMap[$hookName] ?? null;

        if($hookNameHashMethod === null) throw new \Exception('Unknown hook name: '.$hookName);

        return $this->$hookNameHashMethod();
    }

    /**
     * 2023-07-04
     * This is called immediately when a query builder method is called.
     *
     * This method should never call any methods that add operations to the builder.
     * @param ModelQueryBuilder $iBuilder
     * @param array $arguments
     * @return bool
     */
    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool { return true; }

    public function hasOnAdd(): bool { return static::funcHasBeenOverriden('onAdd'); }

    /**
     * 2023-07-04
     * This is called as the first thing when the query is executed but before
     * the actual database operation (shark query) is executed.
     *
     * This method can be asynchronous.
     * You may call methods that add operations to to the builder.
     * @param ModelQueryBuilderOperationSupport $iBuilder
     * @param array $arguments
     * @return bool
     */
    public function onBefore1(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool { return true; }

    public function hasOnBefore1(): bool { return static::funcHasBeenOverriden('onBefore1'); }

    public function onBefore2(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool { return true; }

    public function hasOnBefore2(): bool { return static::funcHasBeenOverriden('onBefore2'); }

    public function onBefore3(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool { return true; }

    public function hasOnBefore3(): bool { return static::funcHasBeenOverriden('onBefore3'); }
    /**
     * 2023-07-04
     * This is called as the last thing when the query is executed but before
     * the actual database operation (shark query) is executed. If your operation
     * needs to call other query building operations (methods that add QueryBuilderOperations)
     * this is the best and last place to do it.
     *
     * This method must be synchronous.
     * You may call methods that add operations to to the builder.
     * @param ModelQueryBuilderOperationSupport $iBuilder
     * @return bool
     */
    public function onBuild(ModelQueryBuilderOperationSupport $iBuilder): void { }

    public function hasOnBuild(): bool { return static::funcHasBeenOverriden('onBuild'); }

    /**
     * 2023-07-04
     * This is called when the shark query is built. Here you should only call shark
     * methods. You may call getters and other immutable methods of the `builder`
     * but you should never call methods that add QueryBuilderOperations.
     *
     * This method must be synchronous.
     * This method should never call any methods that add operations to the builder.
     * This method should always return the shark query builder.
     *
     * @param ModelQueryBuilder|ModelQueryBuilderOperationSupport $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder) { return $iQueryBuilder; }

    public function hasOnBuildQueryBuilder(): bool { return static::funcHasBeenOverriden('onBuildQueryBuilder'); }

    /**
     * 2023-07-04
     * The raw shark result is passed to this method right after the database query
     * has finished. This method may modify it and return the modified rows. The
     * rows are automatically converted to models (if possible) after this hook
     * is called.
     *
     * This method can be asynchronous.
     * @param QueryBuilder $iSharkQueryBuilder
     * @return array
     */
    public function onRawResult(ModelQueryBuilderOperationSupport $iBuilder, array $rows) { return $rows; }

    public function hasOnRawResult(): bool { return static::funcHasBeenOverriden('onRawResult'); }

    /**
     * 2023-07-04
     * The raw shark result is passed to this method right after the database query
     * has finished. This method may modify it and return the modified rows. The
     * rows are automatically converted to models (if possible) after this hook
     * is called.
     *
     * This method can be asynchronous.
     * @param ModelQueryBuilderOperationSupport $iBuilder
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter1(ModelQueryBuilderOperationSupport $iBuilder, &$result) { return $result; }

    public function hasOnAfter1(): bool { return static::funcHasBeenOverriden('onAfter1'); }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$result) { return $result; }

    public function hasOnAfter2(): bool { return static::funcHasBeenOverriden('onAfter2'); }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$result) { return $result; }

    public function hasOnAfter3(): bool { return static::funcHasBeenOverriden('onAfter3'); }

    /**
     * 2023-07-04
     * This method can be implemented to return another operation that will replace
     * this one. This method is called after all `onBefore` and `onBuild` hooks
     * but before the database query is executed.
     *
     * This method must return a ModelQueryBuilderOperationSupport instance.
     * @param ModelQueryBuilderOperationSupport $iBuilder
     * @return ModelQueryBuilderOperationSupport
     */
    public function queryExecutor(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperationSupport { return null; }

    public function hasQueryExecutor(): bool { return static::funcHasBeenOverriden('queryExecutor'); }

    /**
     * 2023-07-04
     * This is called if an error occurs in the query execution.
     *
     * This method must return a QueryBuilder instance.
     * @param ModelQueryBuilder $iBuilder
     * @param \Throwable $error
     */
    public function onError(ModelQueryBuilder $iBuilder, ...$arguments) { }

    public function hasOnError(): bool { return static::funcHasBeenOverriden('onError'); }

    /**
     * 2023-07-04
     * Returns the "find" equivalent of this operation.
     *
     * For example an operation that finds an item and updates it
     * should return an operation that simply finds the item but
     * doesn't update anything. An insert operation should return
     * null since there is no find equivalent for it etc.
     * @param QueryBuilder $iSharkQueryBuilder
     * @return ModelQueryBuilderOperation
     */
    public function toFindOperation(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperation { return $this; }

    protected function hasToFindOperation(): bool { return false; }

    /**
     *
     * Given a set of operations, returns true if any of this operation's
     * ancestor operations are included in the set.
     * @param array<int, ModelQueryBuilderOperation> $ancestorsSet
     */
    public function isAncestorInSet(array $ancestorsSet): bool
    {
        $ancestor = $this->getParentOperation();

        if($ancestor === null) return false;

        $ancestorsSetHash = array_map(function(ModelQueryBuilderOperation $operation) { return $operation->_identifier; }, $ancestorsSet);

        while($ancestor !== null)
        {
            $ancestorName = (new \ReflectionClass($ancestor))->getShortName();
            $isAncestorInSet = in_array($ancestor->_identifier, $ancestorsSetHash, true);

            if(in_array($ancestor->_identifier, $ancestorsSetHash, true)) return true;

            $ancestor = $ancestor->getParentOperation();
        }
        
        return false;
    }

    /**
     * 2023-07-04
     * Add an operation as a child operation. `hookName` must be the
     * name of the parent operation's hook that called this method.
     * @param string $hookName
     * @param ModelQueryBuilderOperation $operation
     */
    public function addChildOperation(string $hookName, ModelQueryBuilderOperation $operation): void
    {
        $operation->setAdderHookName($hookName);
        $operation->setParentOperation($this);

        $this->childOperations[] = $operation;
    }

    /**
     * 2023-07-04
     * Removes a single child operation from this operation.
     * @param ModelQueryBuilderOperation $operation
     */
    public function removeChildOperation(ModelQueryBuilderOperation $operation): void
    {
        $this->childOperations = array_filter($this->childOperations, function(ModelQueryBuilderOperation $childOperation) use ($operation)
        {
            return $childOperation !== $operation;
        });

        $operation->setParentOperation(null);
    }

    /**
     * 2023-07-04
     * Replaces a single child operation
     * @param ModelQueryBuilderOperation $operation
     */
    public function replaceChildOperation(ModelQueryBuilderOperation $oldOperation, ModelQueryBuilderOperation $newOperation): void
    {
        $this->childOperations = array_map(function(ModelQueryBuilderOperation $childOperation) use ($oldOperation, $newOperation)
        {
            if($childOperation === $oldOperation) return $newOperation;

            return $childOperation;
        }, $this->childOperations);

        $oldOperation->setParentOperation(null);
        $newOperation->setParentOperation($this);
    }

    public function removeChildOperationByHookName(string $hookName): void
    {
        $this->childOperations = array_filter($this->childOperations, function(ModelQueryBuilderOperation $childOperation) use ($hookName)
        {
            return $childOperation->adderHookName !== $hookName;
        });
    }

    /**
     * 2023-07-04
     * Iterates through all descendant operations recursively.
     * @param callable $callback
     * @return bool
     */
    public function forEachDescendantOperation(callable $callback): bool
    {
        foreach($this->childOperations as $childOperation)
        {
            $result = $callback($childOperation);

            if($result === false) return false;

            $descendantResult = $childOperation->forEachDescendantOperation($callback);

            if($descendantResult === false) return false;
        }

        return true;
    }

}
