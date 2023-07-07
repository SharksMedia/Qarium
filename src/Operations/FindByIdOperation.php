<?php

/**
 * 2023-07-04
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;

class FindByIdOperation extends ModelQueryBuilderOperation
{
    private ?string $id;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->id = $this->options['id'] ?? null;
    }

    public function onAdd(ModelQueryBuilder $builder, array $arguments): bool
    {
        if($this->id === null) $this->id = $arguments[0];

        return parent::onAdd($builder, $arguments);
    }

    public function onBuild(ModelQueryBuilder $builder): void
    {
        $builder->whereComposite($builder->fullIdColumn(), $this->id);
    }

}
