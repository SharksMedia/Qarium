<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;

class InstanceUpdateOperation extends UpdateOperation
{
    private Model $instance;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->instance            = $options['instance'];
        $this->modelOptions['old'] = $options['instance'];
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $returnValue = parent::onAdd($iBuilder, ...$arguments);

        if ($this->iModel === null)
        {
            $this->iModel = $this->instance;
        }

        return $returnValue;
    }

    /**
     * @param ModelSharQOperationSupport|ModelSharQ $iBuilder
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

        $iBuilder->findById($this->instance->getID());
    }

    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        // The result may be an object if `returning` was used.
        if (is_array($result))
        {
            $result = $result[0];
        }

        $result = parent::onAfter2($iBuilder, $result);
        $this->instance->lset($this->iModel);

        if (is_object($result))
        {
            $this->instance->lset($result);
        }

        return $result;
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return new InstanceFindOperation('find', ['instance' => $this->instance]);
    }
}


