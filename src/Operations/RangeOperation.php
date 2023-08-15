<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class RangeOperation extends ModelQueryBuilderOperation
{
    private ModelQueryBuilder $resultSizeBuilder;
     
    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        if(count($arguments) !== 2) return true;

        $start = $arguments[0];
        $end = $arguments[1];

        // Need to set these here instead of `onBuildKnex` so that they
        // don't end up in the resultSize query.
        $iBuilder->limit($end - $start + 1)->offset($start);

        return true;
    }

    public function onBefore1(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->resultSizeBuilder = clone $iBuilder;

        return parent::onBefore1($iBuilder, ...$arguments);
    }

    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        $resultSize = $this->resultSizeBuilder->resultSize();

        return [
            'results'=>$result,
            'total'=>$resultSize,
        ];
    }
}


