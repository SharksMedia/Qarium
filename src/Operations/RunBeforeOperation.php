<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class RunBeforeOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];
        return true;
    }

    public function onBefore1(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        if($this->closure === null) return true;

        $closure = $this->closure;

        $result = $closure($iBuilder, ...$arguments);

        return $result ?? true;
    }
}


