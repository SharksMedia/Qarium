<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class OnBuildOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];
        return true;
    }

    public function onBuild(ModelQueryBuilderOperationSupport $iBuilder): void
    {
        if($this->closure === null) return;

        $closure = $this->closure;

        $closure($iBuilder);
    }
}


