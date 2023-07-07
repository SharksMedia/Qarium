<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryBuilder;

class QueryBuilderOperation extends ModelQueryBuilderOperation
{
    public function onBuildQueryBuilder(QueryBuilder $iQueryBuilder, ModelQueryBuilder $builder): QueryBuilder
    {
        $functionName = $this->getName();
        
    }
}
