<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\Relations\RelationProperty;

class Utilities
{
    public const SMALL_ARRAY_SIZE = 10;

    public static function array_flatten(array $array): array
    {// Generated with copilot
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return)
        {
            $return[] = $a;
        });

        return $return;
    }

    public static function array_union(array $array1, array $array2): array
    {// Generated with copilot
        if (count($array1) < self::SMALL_ARRAY_SIZE && count($array2) < self::SMALL_ARRAY_SIZE)
        {
            return self::array_union_small($array1, $array2);
        }

        return self::array_union_generic($array1, $array2);
    }

    public static function array_union_small(array $array1, array $array2): array
    {// Generated with copilot
        $all = $array1;

        foreach ($array2 as $item)
        {
            if (!in_array($item, $all))
            {
                $all[] = $item;
            }
        }

        return $all;
    }

    public static function array_union_generic(array $array1, array $array2): array
    {
        $all = [];

        foreach ($array1 as $item)
        {
            $all[] = $item;
        }

        foreach ($array2 as $item)
        {
            $all[] = $item;
        }

        return array_unique($all);
    }

    public static function parseFieldExpression($expr)
    {
        // static $cache = [];

        // $parsedExpression = $cache[$expr] ?? null;

        // if($parsedExpression !== null) return $parsedExpression;

        // 2023-07-12 We don't support field expressions yet
        $parsedExpression = (object)
        [
            'column' => $expr,
            'table'  => null,
            'access' => [], // NOTE: Not sure what this is
        ];

        $parsedExpression = self::preprocessParsedExpression($parsedExpression);

        // $cache[$expr] = $parsedExpression;

        return $parsedExpression;
    }

    private static function preprocessParsedExpression(object $parsedExpr)
    {
        $columnParts = array_map(function($column)
        { return trim($column); }, explode('.', $parsedExpr->column));
        $parsedExpr->column = $columnParts[count($columnParts) - 1];

        if (count($columnParts) >= 2)
        {
            $parsedExpr->table = implode(',', array_slice($columnParts, 0, count($columnParts) - 1));
        }
        else
        {
            $parsedExpr->table = null;
        }

        return $parsedExpr;
    }

    public static function uuid(string $data = null): string
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

        foreach ($items as $item)
        {
            $key = ($keyGetter !== null) ? $keyGetter($item) : $item;

            if (!isset($groups[$key]))
            {
                $groups[$key] = [];
            }

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

    public static function isSharQ($value): bool
    {
        return $value instanceof \Sharksmedia\SharQ\SharQ;
    }

    public static function isSharQRaw($value): bool
    {
        return $value instanceof \Sharksmedia\SharQ\Statement\Raw;
    }

    public static function isSharQRawConvertable($value): bool
    {
        return false;
    }

    public static function isModelSharQBase($value): bool
    {
        return $value instanceof \Sharksmedia\Qarium\ModelSharQBase;
    }

    public static function normalizeIds($ids, RelationProperty $prop, $opt = [])
    {
        $isComposite = $prop->getSize() > 1;

        if ($isComposite)
        {
            if (is_array($ids))
            {
                if (is_array($ids[0]))
                {
                    $ret = array_fill(0, count($ids), null);

                    for ($i = 0; $i < count($ids); ++$i)
                    {
                        $ret[$i] = self::convertIdArrayToObject($ids[$i], $prop);
                    }
                }
                elseif (is_array($ids[0]))
                {
                    $ret = array_fill(0, count($ids), null);

                    for ($i = 0; $i < count($ids); ++$i)
                    {
                        $ret[$i] = self::ensureObject($ids[$i], $prop);
                    }
                }
                else
                {
                    $ret = [self::convertIdArrayToObject($ids, $prop)];
                }
            }
            elseif (is_array($ids))
            {
                $ret = [$ids];
            }
            else
            {
                throw new \Exception("invalid composite key ".json_encode($ids));
            }
        }
        else
        {
            if (is_array($ids))
            {
                if (is_array($ids[0]))
                {
                    $ret = array_fill(0, count($ids), null);

                    for ($i = 0; $i < count($ids); ++$i)
                    {
                        $ret[$i] = self::ensureObject($ids[$i]);
                    }
                }
                else
                {
                    $ret = array_fill(0, count($ids), []);

                    for ($i = 0; $i < count($ids); ++$i)
                    {
                        $ret[$i] = [];
                        $prop->setProp($ret[$i], 0, $ids[$i]);
                    }
                }
            }
            elseif (is_array($ids))
            {
                $ret = [$ids];
            }
            else
            {
                $obj = [];
                $prop->setProp($obj, 0, $ids);
                $ret = [$obj];
            }
        }

        self::checkProperties($ret, $prop);

        if ($opt['arrayOutput'])
        {
            return self::normalizedToArray($ret, $prop);
        }
        else
        {
            return $ret;
        }
    }

    public static function convertIdArrayToObject($ids, $prop)
    {
        if (!is_array($ids))
        {
            throw new \Exception("invalid composite key ".json_encode($ids));
        }

        if (count($ids) != $prop->size)
        {
            throw new \Exception("composite identifier ".json_encode($ids)." should have ".$prop->size." values");
        }

        $obj = [];

        for ($i = 0; $i < count($ids); ++$i)
        {
            $prop->setProp($obj, $i, $ids[$i]);
        }

        return $obj;
    }

    public static function ensureObject($ids)
    {
        if (is_array($ids))
        {
            return $ids;
        }
        else
        {
            throw new \Exception("invalid composite key ".json_encode($ids));
        }
    }

    public static function checkProperties($ret, RelationProperty $prop)
    {
        for ($i = 0; $i < count($ret); ++$i)
        {
            $obj = $ret[$i];

            for ($j = 0; $j < $prop->getSize(); ++$j)
            {
                $val = $prop->getProp($obj, $j);

                if (!$prop->hasProp($obj, $j))
                {
                    throw new \Exception("expected id ".json_encode($obj)." to have property ".$prop->getPropDescription($j));
                }
            }
        }
    }

    public static function normalizedToArray($ret, RelationProperty $prop)
    {
        $arr = array_fill(0, count($ret), null);

        for ($i = 0; $i < count($ret); ++$i)
        {
            $arr[$i] = $prop->getProps($ret[$i]);
        }

        return $arr;
    }

    public static function get($obj, $path)
    {
        for ($i = 0, $l = count($path); $i < $l; ++$i)
        {
            $key = $path[$i];

            // if (!isObject($obj)) {
            //     return null;
            // }

            // Check if the key exists in the object before accessing it
            if (!array_key_exists($key, $obj))
            {
                return null;
            }

            $obj = $obj[$key];
        }

        return $obj;
    }

    public static function has($obj, $path)
    {
        $has = count($path) > 0;

        foreach ($path as $key)
        {
            $has = $has && array_key_exists($key, $obj);
        }

        return $has;
    }

    public static function set(&$obj, $path, $value)
    {
        $inputObj = &$obj;

        for ($i = 0, $l = count($path) - 1; $i < $l; ++$i)
        {
            $key = $path[$i];

            if (!self::isSafeKey($key))
            {
                return $inputObj;
            }

            if (!isset($obj[$key]) || !is_array($obj[$key]))
            {
                $nextKey = $path[$i + 1];

                if (is_numeric($nextKey))
                {
                    $obj[$key] = [];
                }
                else
                {
                    $obj[$key] = [];
                }
            }

            $obj = &$obj[$key];
        }

        if (count($path) > 0 && is_array($obj))
        {
            $key = $path[count($path) - 1];

            if (self::isSafeKey($key))
            {
                $obj[$key] = $value;
            }
        }

        return $inputObj;
    }

    // Assuming you have the isSafeKey function similar to JavaScript
    private static function isSafeKey($key)
    {
        return is_string($key) || is_numeric($key);
    }
}
