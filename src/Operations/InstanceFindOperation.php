<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;

class InstanceFindOperation extends FindOperation
{
    private Model $instance;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->instance = $options['instance'];
    }

    /**
     * @param ModelSharQOperationSupport|ModelSharQ $iBuilder
     * @return bool
     * @throws \Exception
     */
    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        if (!$this->instance->lhasIDs())
        {
            $idsStr = implode(',', $this->instance->getTableIDs());

            throw new \Exception("one of the identifier columns [$idsStr] is null or undefined. Have you specified the correct identifier column for the model '".$this->instance::class."' using the 'idColumn' property?");
        }

        $iBuilder->findById($this->instance->getID());
    }
}

