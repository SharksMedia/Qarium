<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Throwable;

class OnErrorOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $function = null;
    
    public function onAdd(ModelQueryBuilder $builder, array $arguments): bool
    {
        $this->function = $arguments[0] ?? null;
    }

    public function onError(ModelQueryBuilder $builder, array $arguments): void
    {
        if($this->function !== null)
        {
            $func = $this->function;
            $func($builder, $arguments);
        }
    }
}
