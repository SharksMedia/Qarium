<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;

class OnErrorOperation extends ModelSharQOperation
{
    private ?\Closure $function = null;
    
    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->function = $arguments[0] ?? null;

        return true;
    }

    public function onError(ModelSharQ $iBuilder, ...$arguments)
    {
        if($this->function === null) return;

        $func = $this->function;
        return $func($iBuilder, ...$arguments);
    }
}
