<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

class Utilities
{
    public const SMALL_ARRAY_SIZE = 10;

    public static function array_flatten(array $array): array
    {// Generated with copilot
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    public static function array_union(array $array1, array $array2): array
    {// Generated with copilot
        if(count($array1) < self::SMALL_ARRAY_SIZE && count($array2) < self::SMALL_ARRAY_SIZE)
        {
            return self::array_union_small($array1, $array2);
        }

        return self::array_union_generic($array1, $array2);
    }

    public static function array_union_small(array $array1, array $array2): array
    {// Generated with copilot
        $all = $array1;
        foreach($array2 as $item)
        {
            if(!in_array($item, $all)) $all[] = $item;
        }
        return $all;
    }

    public static function array_union_generic(array $array1, array $array2): array
    {
        $all = [];
        foreach($array1 as $item) $all[] = $item;
        foreach($array2 as $item) $all[] = $item;

        return array_unique($all);
    }

    public static function parseFieldExpression($expr)
    {
        static $cache = [];

        $parsedExpression = $cache[$expr] ?? null;

        if($parsedExpression !== null) return $parsedExpression;

        // 2023-07-12 We don't support field expressions yet
        $parsedExpression = (object)
        [
            'column'=>$expr,
            'table'=>null,
            'access'=>[], // NOTE: Not sure what this is
        ];

        $parsedExpression = self::preprocessParsedExpression($parsedExpression);

        $cache[$expr] = $parsedExpression;

        return $parsedExpression;
    }

    private static function preprocessParsedExpression(object $parsedExpr)
    {
        
        $columnParts = array_map(function($column){ return trim($column); }, explode('.', $parsedExpr->column));
        $parsedExpr->column = $columnParts[count($columnParts) - 1];

        if(count($columnParts) >= 2)
        {
            $parsedExpr->table = implode(',', array_slice($columnParts, 0, count($columnParts) - 1));
        }
        else
        {
            $parsedExpr->table = null;
        }

        return $parsedExpr;
    }

    public static function uuid(string $data=null): string
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function groupBy($items, $keyGetter = null)
    {
        $groups = [];

        foreach($items as $item)
        {
            $key = ($keyGetter !== null) ? $keyGetter($item) : $item;

            if(!isset($groups[$key])) $groups[$key] = [];

            $groups[$key][] = $item;
        }

        return $groups;
    }

    /**
     * @param array<int,mixed> $array
     */
    public static function arrayRemoveFalsey(array $array): array
    {// 2023-05-10
        return array_filter($array, function($value)
        {// 2023-05-10
            return (bool)$value;
        });
    }

    public static function isQueryBuilder($value): bool
    {
        return $value instanceof \Sharksmedia\QueryBuilder\QueryBuilder;
    }

    public static function isQueryBuilderRaw($value): bool
    {
        return $value instanceof \Sharksmedia\QueryBuilder\Statement\Raw;
    }

    public static function isQueryBuilderRawConvertable($value): bool
    {
        return false;
    }

    public static function isModelQueryBuilderBase($value): bool
    {
        return $value instanceof \Sharksmedia\Objection\ModelQueryBuilderBase;
    }
}
