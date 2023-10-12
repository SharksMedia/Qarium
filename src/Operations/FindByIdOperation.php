<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class FindByIdOperation extends ModelSharQOperation
{
    private $id;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->id = $this->options['id'] ?? null;
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        if ($this->id === null)
        {
            $this->id = $arguments[0];
        }

        return parent::onAdd($iBuilder, $arguments);
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        $iBuilder->whereComposite($iBuilder->getFullIdColumn(), $this->id);
    }
}
