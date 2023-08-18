<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class OnBuildOperation extends ModelSharQOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];
        return true;
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        if($this->closure === null) return;

        $closure = $this->closure;

        $closure($iBuilder);
    }
}


