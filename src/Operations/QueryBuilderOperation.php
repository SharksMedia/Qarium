<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;

class QueryBuilderOperation extends ObjectionToQueryBuilderConvertingOperation
{
    /**
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        $functionName = $this->getName();
        
        return $iQueryBuilder->{$functionName}(...$this->getArguments($iBuilder));
    }
}
