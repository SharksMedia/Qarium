<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Raw;

class WhereInCompositeOperation extends ObjectionToQueryBuilderConvertingOperation
{
    private $prefix;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->prefix = $this->options['prefix'] ?? null;
    }

    /**
     * @param ModelQueryBuilderOperationSupport $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        $whereInArgs = self::buildWhereInArgs($iBuilder->getQueryBuilder(), ...$this->getArguments($iBuilder));

        codecept_debug($whereInArgs);

        if($this->prefix === 'not') return $iQueryBuilder->whereNotIn(...$whereInArgs);

        return $iQueryBuilder->whereIn(...$whereInArgs);
    }

    private static function buildWhereInArgs($iQueryBuilder, $columns, $values)
    {
        codecept_debug(self::isCompositeKey($columns));
        if(self::isCompositeKey($columns))
        {
            return self::buildCompositeArgs($iQueryBuilder, $columns, $values);
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

    private static function buildCompositeArgs($iQueryBuilder, $columns, $values)
    {
        if(is_array($values))
        {
            return self::buildCompositeValueArgs($columns, $values);
        }
        else
        {
            return self::buildCompositeSubqueryArgs($iQueryBuilder, $columns, $values);
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

    private static function buildCompositeSubqueryArgs($iQueryBuilder, $columns, $subquery)
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
        else if(!($values instanceof QueryBuilder))
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
