<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Join;

class FirstOperation extends ModelQueryBuilderOperation
{
    /**
     * @param ModelQueryBuilder|ModelQueryBuilderOperationSupport $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        if($iQueryBuilder !== null && !($iQueryBuilder instanceof QueryBuilder) && !($iQueryBuilder instanceof Join))  throw new \Exception('Invalid QueryBuilder type: '.get_class($iQueryBuilder));

        $modelClass = $iBuilder->getModelClass();

        if($iBuilder->isFind() && $modelClass::USE_LIMIT_IN_FIRST) $iQueryBuilder->limit(1);

        return $iQueryBuilder;
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        if(is_array($result)) return reset($result);

        return $result;
    }
}


