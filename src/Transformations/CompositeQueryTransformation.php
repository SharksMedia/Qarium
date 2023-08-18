<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium\Transformations;

use Sharksmedia\Qarium\ModelSharQ;

class CompositeQueryTransformation extends QueryTransformation
{
    /**
     * @var array<int, QueryTransformation>
     */
    protected $iTransformations;

    /**
     * @param array<int, QueryTransformation> $iTransformations
     */
    public function __construct(array $iTransformations)
    {
        $this->iTransformations = $iTransformations;
    }

    public function onConvertSharQBase(ModelSharQ $iQuery, ModelSharQ $iBuilder): ModelSharQ
    {
        foreach($this->iTransformations as $iTransformation)
        {
            $iQuery = $iTransformation->onConvertSharQBase($iQuery, $iBuilder);
        }

        return $iQuery;
    }

}

