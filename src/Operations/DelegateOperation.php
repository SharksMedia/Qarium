<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\SharQ\SharQ;

// Operation that simply delegates all calls to the operation passed
// to to the constructor in `opt['delegate']`.
class DelegateOperation extends ModelSharQOperation
{
    protected $delegate;

    public function __construct(string $name, array $options)
    {
        parent::__construct($name, $options);

        if (!isset($options['delegate']))
        {
            throw new \Exception('DelegateOperation requires an operation to delegate to');
        }

        $this->delegate = $options['delegate'];
    }

    public function is(string $className): bool
    {
        return parent::is($className) || $this->delegate->is($className);
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        return $this->delegate->onAdd($iBuilder, ...$arguments);
    }

    public function onBefore1(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        return $this->delegate->onBefore1($iBuilder, ...$arguments);
    }

    public function hasOnBefore1(): bool
    {
        return $this->delegate->hasOnBefore1();
    }

    public function onBefore2(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        return $this->delegate->onBefore2($iBuilder, ...$arguments);
    }

    public function hasOnBefore2(): bool
    {
        return $this->delegate->hasOnBefore2();
    }

    public function onBefore3(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        return $this->delegate->onBefore3($iBuilder, ...$arguments);
    }

    public function hasOnBefore3(): bool
    {
        return $this->delegate->hasOnBefore3();
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        $this->delegate->onBuild($iBuilder);
    }

    public function hasOnBuild(): bool
    {
        return $this->delegate->hasOnBuild();
    }

    /**
     * @param ModelSharQ|ModelSharQOperationSupport $iBuilder
     * @param SharQ|Sharksmedia\Qarium\Operations\Join|null $iSharQ
     * @return SharQ|Sharksmedia\Qarium\Operations\Join|null
     */
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        return $this->delegate->onBuildSharQ($iBuilder, $iSharQ);
    }

    public function hasOnBuildSharQ(): bool
    {
        return $this->delegate->hasOnBuildSharQ();
    }

    /**
     * 2023-07-04
     * The raw shark result is passed to this method right after the database query
     * has finished. This method may modify it and return the modified rows. The
     * rows are automatically converted to models (if possible) after this hook
     * is called.
     *
     * This method can be asynchronous.
     * @param SharQ $iSharkSharQ
     * @return array
     */
    public function onRawResult(ModelSharQOperationSupport $iBuilder, array $rows): array
    {
        return $this->delegate->onRawResult($iBuilder, $rows);
    }

    public function hasOnRawResult(): bool
    {
        return $this->delegate->hasOnRawResult();
    }

    /**
     * This method can be asynchronous.
     * @param ModelSharQOperationSupport $iBuilder
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter1(ModelSharQOperationSupport $iBuilder, &$result)
    {
        return $this->delegate->onAfter1($iBuilder, $result);
    }

    public function hasOnAfter1(): bool
    {
        return $this->delegate->hasOnAfter1();
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        return $this->delegate->onAfter2($iBuilder, $result);
    }

    public function hasOnAfter2(): bool
    {
        return $this->delegate->hasOnAfter2();
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelSharQOperationSupport $iBuilder, &$result)
    {
        return $this->delegate->onAfter3($iBuilder, $result);
    }

    public function hasOnAfter3(): bool
    {
        return $this->delegate->hasOnAfter3();
    }

    public function queryExecutor(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperationSupport
    {
        return $this->delegate->queryExecutor($iBuilder);
    }

    public function hasQueryExecutor(): bool
    {
        return $this->delegate->hasQueryExecutor();
    }

    /**
     * 2023-07-04
     * This is called if an error occurs in the query execution.
     *
     * This method must return a SharQ instance.
     * @param ModelSharQ $iBuilder
     * @param \Throwable $error
     * @return void
     */
    public function onError(ModelSharQ $iBuilder, ...$arguments)
    {
        return $this->delegate->onError($iBuilder, ...$arguments);
    }

    public function hasOnError(): bool
    {
        return $this->delegate->hasOnError();
    }

    /**
     * 2023-07-04
     * Returns the "find" equivalent of this operation.
     *
     * For example an operation that finds an item and updates it
     * should return an operation that simply finds the item but
     * doesn't update anything. An insert operation should return
     * null since there is no find equivalent for it etc.
     * @param SharQ $iSharkSharQ
     * @return ModelSharQOperation
     */
    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return $this->delegate->toFindOperation($iBuilder);
    }

    public function hasToFindOperation(): bool
    {
        return $this->delegate->hasToFindOperation();
    }
}
