<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\JoinBuilder;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Join;

class QueryBuilderOperation extends ObjectionToQueryBuilderConvertingOperation
{
    /**
     * @param ModelQueryBuilder|ModelQueryBuilderOperationSupport $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        if($iQueryBuilder !== null && !($iQueryBuilder instanceof QueryBuilder) && !($iQueryBuilder instanceof Join))  throw new \Exception('Invalid QueryBuilder type: '.get_class($iQueryBuilder));

        $functionName = $this->getName();

        return $iQueryBuilder->{$functionName}(...$this->getArguments($iBuilder));
    }
}
