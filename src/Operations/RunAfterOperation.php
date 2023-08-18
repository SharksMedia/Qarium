<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class RunAfterOperation extends ModelSharQOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];
        return true;
    }

    public function onAfter3(ModelSharQOperationSupport $iBuilder, &$result)
    {
        if($this->closure === null) return $result;

        $closure = $this->closure;

        return $closure($iBuilder, $result);
    }
}



