<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class FindByIdsOperation extends ModelQueryBuilderOperation
{
    private ?array $ids;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->ids = null;
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->ids = $arguments[0];

        return parent::onAdd($iBuilder, $arguments);
    }

    public function onBuild(ModelQueryBuilderOperationSupport $iBuilder): void
    {
        /** @var ModelQueryBuilder $iBuilder */
        $iBuilder->whereInComposite($iBuilder->getFullIdColumn(), $this->ids);
    }

}
