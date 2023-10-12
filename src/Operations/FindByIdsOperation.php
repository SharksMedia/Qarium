<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;

class FindByIdsOperation extends ModelSharQOperation
{
    private ?array $ids;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->ids = null;
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->ids = $arguments[0];

        return parent::onAdd($iBuilder, $arguments);
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        /** @var ModelSharQ $iBuilder */
        $iBuilder->whereInComposite($iBuilder->getFullIdColumn(), $this->ids);
    }
}
