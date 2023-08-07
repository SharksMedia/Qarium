<?php

declare(strict_types=1);

namespace Sharksmedia\Objection\Transformations;

use Sharksmedia\Objection\ModelQueryBuilder;

class QueryTransformation
{
    public function onConvertQueryBuilderBase(ModelQueryBuilder $iQuery, ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        return $iQuery;
    }
}
