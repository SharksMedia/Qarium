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

class WhereCompositeOperation extends ObjectionToQueryBuilderConvertingOperation
{
    /**
     * @param ModelQueryBuilder|ModelQueryBuilderOperationSupport $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        $arguments = $this->getArguments($iBuilder);

        if(count($arguments) === 2)
        {
            array_splice($arguments, 1, 0, '=');
        }
        else if(count($arguments) !== 3)
        {
            throw new \Exception('Invalid number of arguments ' . count($arguments));
        }

        $whereArgs = $this->buildWhereArgs(...$arguments);

        return $iQueryBuilder->where(...$whereArgs);
    }

    private function buildWhereArgs($cols, string $op, $values): array
    {
        if($this->isNormalWhere($cols, $values))
        {
            return $this->buildNormalWhereArgs($cols, $op, $values);
        }
        else if($this->isCompositeWhere($cols, $values))
        {
            return $this->buildCompositeWhereArgs($cols, $op, $values);
        }
        else
        {
            throw new \Exception('both cols and values must have same dimensions');
        }
    }

    private function isNormalWhere($cols, $values): bool
    {
        return (
            (!is_array($cols) || count($cols) === 1) &&
            (!is_array($values) || count($values) === 1)
        );
    }

    private function buildNormalWhereArgs($cols, string $op, $values): array
    {
        return [self::asSingle($cols), $op, self::asSingle($values)];
    }

    private function isCompositeWhere($cols, $values): bool
    {
        return is_array($cols) && is_array($values) && count($cols) === count($values);
    }

    private function buildCompositeWhereArgs($cols, string $op, $values): array
    {
        return [
            function($builder) use($cols, $op, $values)
            {
                for($i = 0, $l = count($cols); $i < $l; ++$i)
                {
                    $builder->where($cols[$i], $op, $values[$i]);
                }
            }
        ];
    }

    private static function asSingle($value)
    {
        return is_array($value) ? $value[0] : $value;
    }

}
