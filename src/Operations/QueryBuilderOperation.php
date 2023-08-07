<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Join;

class QueryBuilderOperation extends ObjectionToQueryBuilderConvertingOperation
{
    /**
     * @param ModelQueryBuilder $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, $iQueryBuilder)
    {
        if($iQueryBuilder !== null && !($iQueryBuilder instanceof QueryBuilder) && !($iQueryBuilder instanceof Join))  throw new \Exception('Invalid QueryBuilder type: '.get_class($iQueryBuilder));

        $functionName = $this->getName();

        return $iQueryBuilder->{$functionName}(...$this->getArguments($iBuilder));
    }
}
