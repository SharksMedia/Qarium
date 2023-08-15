<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class InstanceDeleteOperation extends DeleteOperation
{
    private Model $instance;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->instance = $options['instance'];
    }

    public function onBefore2(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->instance->lbeforeDelete($iBuilder->getContext());

        return parent::onBefore2($iBuilder, ...$arguments);
    }

    /**
     * @param ModelQueryBuilderOperationSupport|ModelQueryBuilder $iBuilder
     * @return bool
     * @throws \Exception
     */
    public function onBuild(ModelQueryBuilderOperationSupport $iBuilder): void
    {
        parent::onBuild($iBuilder);

        if(!$this->instance->lhasIDs())
        {
            $idsStr = implode(',', $this->instance->getTableIDs());

            throw new \Exception("one of the identifier columns [$idsStr] is null or undefined. Have you specified the correct identifier column for the model '".$this->instance::class."' using the 'idColumn' property?");
        }

        $iBuilder->findByIds($this->instance->getID());
    }

    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        if(is_array($result)) $result = $result[0];

        $this->instance->lafterDelete($iBuilder->getContext());

        return parent::onAfter2($iBuilder, $result);
    }

    public function toFindOperation(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperation
    {
        return new InstanceFindOperation('find', ['instance' => $this->instance]);
    }
}

