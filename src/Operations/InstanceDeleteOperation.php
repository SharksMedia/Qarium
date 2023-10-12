<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;

class InstanceDeleteOperation extends DeleteOperation
{
    private Model $instance;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->instance = $options['instance'];
    }

    public function onBefore2(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->instance->lbeforeDelete($iBuilder->getContext());

        return parent::onBefore2($iBuilder, ...$arguments);
    }

    /**
     * @param ModelSharQOperationSupport|ModelSharQ $iBuilder
     * @return bool
     * @throws \Exception
     */
    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        parent::onBuild($iBuilder);

        if (!$this->instance->lhasIDs())
        {
            $idsStr = implode(',', $this->instance->getTableIDs());

            throw new \Exception("one of the identifier columns [$idsStr] is null or undefined. Have you specified the correct identifier column for the model '".$this->instance::class."' using the 'idColumn' property?");
        }

        $iBuilder->findByIds($this->instance->getID());
    }

    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        if (is_array($result))
        {
            $result = $result[0];
        }

        $this->instance->lafterDelete($iBuilder->getContext());

        return parent::onAfter2($iBuilder, $result);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return new InstanceFindOperation('find', ['instance' => $this->instance]);
    }
}

