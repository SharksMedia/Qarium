<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

class Utilities
{
    public static function array_flatten(array $array): array
    {// Generated with copilot
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }
}
