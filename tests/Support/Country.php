<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Qarium\Model;

class Country extends Model
{
    protected int       $countryID;
    protected string    $name;
    protected string    $ISOCode2;
    protected string    $ISOCode3;

    public static function getTableName(): string
    {// 2023-06-12
        return 'Countries';
    }

    public static function getTableIDs(): array
    {// 2023-06-12
        return ['countryID'];
    }
}
