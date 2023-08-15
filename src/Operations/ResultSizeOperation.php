<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class ResultSizeOperation extends ModelQueryBuilderOperation
{
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        $iWrapperQueryBuiler = $iBuilder->getQueryBuilder();

        $iBuilder->clear(ModelQueryBuilderOperationSupport::LIMIT_SELECTOR);
        $iBuilder->clear(ModelQueryBuilderOperationSupport::ORDER_BY_SELECTOR);

        $iWrapperQueryBuiler->count('* AS count')
            ->from(function($q) use($iBuilder)
                {
                    $iBuilder->toQueryBuilder($q)->as('test');
                });

        return $iQueryBuilder;
    }
}


