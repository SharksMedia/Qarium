<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryBuilder;

class QueryBuilderOperation extends ObjectionToQueryBuilderConvertingOperation
{
    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, QueryBuilder $iQueryBuilder): QueryBuilder
    {
        $functionName = $this->getName();
        
        return $iQueryBuilder->$functionName(...$this->getArguments($iBuilder));
    }
}
