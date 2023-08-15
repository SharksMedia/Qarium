<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class InstanceInsertOperation extends InsertOperation
{
    private Model $instance;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->instance = $options['instance'];
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $arguments[0] = $this->instance;

        return parent::onAdd($iBuilder, ...$arguments);
    }
}

