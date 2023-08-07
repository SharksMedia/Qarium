<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class OnBuildQueryBuilderOperation extends ModelQueryBuilderOperation
{
    private ?\Closure $closure = null;

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->closure = $arguments[0];

        return true;
    }

    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, $iQueryBuilder)
    {
        if($this->closure === null) return $iQueryBuilder;

        $closure = $this->closure;

        return $closure($iBuilder, $iQueryBuilder);
    }
}



