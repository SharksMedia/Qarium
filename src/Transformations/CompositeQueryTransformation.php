<?php

declare(strict_types=1);

namespace Sharksmedia\Objection\Transformations;

use Sharksmedia\Objection\ModelQueryBuilder;

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

    public function onConvertQueryBuilderBase(ModelQueryBuilder $iQuery, ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        foreach($this->iTransformations as $iTransformation)
        {
            $iQuery = $iTransformation->onConvertQueryBuilderBase($iQuery, $iBuilder);
        }

        return $iQuery;
    }

}

