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
}
