<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;

class FindByIdsOperation extends ModelQueryBuilderOperation
{
    private ?array $ids;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->ids = null;
    }

    public function onAdd(ModelQueryBuilder $builder, array $arguments): bool
    {
        $this->ids = $arguments[0];

        return parent::onAdd($builder, $arguments);
    }

    public function onBuild(ModelQueryBuilder $builder): void
    {
        $builder->whereInComposite($builder->fullIdColumn(), $this->ids);
    }

}
