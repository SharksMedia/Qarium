<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium\Transformations;

use Sharksmedia\Qarium\ModelSharQ;

class QueryTransformation
{
    public function onConvertSharQBase(ModelSharQ $iQuery, ModelSharQ $iBuilder): ModelSharQ
    {
        return $iQuery;
    }
}
