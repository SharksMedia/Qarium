<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Raw;

class WhereInCompositeOperation extends QariumToSharQConvertingOperation
{
    private $prefix;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->prefix = $this->options['prefix'] ?? null;
    }

    /**
     * @param ModelSharQ|ModelSharQOperationSupport $iBuilder
     * @param SharQ|Join|null $iSharQ
     * @return SharQ|Join|null
     */
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        $whereInArgs = self::buildWhereInArgs($iBuilder->getSharQ(), ...$this->getArguments($iBuilder));

        if($this->prefix === 'not') return $iSharQ->whereNotIn(...$whereInArgs);

        return $iSharQ->whereIn(...$whereInArgs);
    }

    private static function buildWhereInArgs($iSharQ, $columns, $values)
    {
        if(self::isCompositeKey($columns))
        {
            return self::buildCompositeArgs($iSharQ, $columns, $values);
        }
        else
        {
            return self::buildNonCompositeArgs($columns, $values);
        }
    }

    private static function isCompositeKey($columns)
    {
        return is_array($columns) && count($columns) > 1;
    }

    private static function buildCompositeArgs($iSharQ, $columns, $values)
    {
        if(is_array($values))
        {
            return self::buildCompositeValueArgs($columns, $values);
        }
        else
        {
            return self::buildCompositeSubqueryArgs($iSharQ, $columns, $values);
        }
    }

    private static function buildCompositeValueArgs($columns, $values)
    {
        if(!is_array($values[0]))
        {
            return [$columns, [$values]];
        }
        else
        {
            return [$columns, $values];
        }
    }

    private static function buildCompositeSubqueryArgs($iSharQ, $columns, $subquery)
    {
        // Might have to use ?? instead of ?
        $sql = '(' . implode(',', array_fill(0, count($columns), '??')) . ')';

        return [new Raw($sql, ...$columns), $subquery];
    }

    private static function buildNonCompositeArgs($columns, $values)
    {
        if(is_array($values))
        {
            $values = self::pickNonNull($values, []);
        }
        else if(!($values instanceof SharQ))
        {
            $values = [$values];
        }

        return [self::asSingle($columns), $values];
    }

    private static function pickNonNull(array $values, $output)
    {
        foreach($values as $val)
        {
            if(is_array($val))
            {
                $output = self::pickNonNull($val, $output);
            }
            else if($val !== null && $val !== '')
            {
                $output[] = $val;
            }
        }

        return $output;
    }

    private static function asSingle($value)
    {
        return is_array($value) ? $value[0] : $value;
    }
}
