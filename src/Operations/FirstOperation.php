<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryBuilder;

class FirstOperation extends ModelQueryBuilderOperation
{
    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, QueryBuilder $iQueryBuilder): QueryBuilder
    {
        $modelClass = $iBuilder->getModelClass();

        if($iBuilder->isFind() && $modelClass::USE_LIMIT_IN_FIRST) $iQueryBuilder->limit(1);

        return $iQueryBuilder;
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelQueryBuilder $iBuilder, &$result)
    {
        if(is_array($result)) return reset($result);

        return $result;
    }
}


