<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class RunAfterOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];
        return true;
    }

    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        if($this->closure === null) return $result;

        $closure = $this->closure;

        return $closure($iBuilder, $result);
    }
}



