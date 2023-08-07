<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class OnErrorOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $function = null;
    
    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->function = $arguments[0] ?? null;

        return true;
    }

    public function onError(ModelQueryBuilder $iBuilder, ...$arguments)
    {
        if($this->function === null) return;

        $func = $this->function;
        return $func($iBuilder, ...$arguments);
    }
}
