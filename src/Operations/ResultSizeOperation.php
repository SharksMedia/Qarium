<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class ResultSizeOperation extends ModelSharQOperation
{
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        $iWrapperQueryBuiler = $iBuilder->getSharQ();

        $iBuilder->clear(ModelSharQOperationSupport::LIMIT_SELECTOR);
        $iBuilder->clear(ModelSharQOperationSupport::ORDER_BY_SELECTOR);

        $iWrapperQueryBuiler->count('* AS count')
            ->from(function($q) use ($iBuilder)
            {
                $iBuilder->toSharQ($q)->as('test');
            });

        return $iSharQ;
    }
}


