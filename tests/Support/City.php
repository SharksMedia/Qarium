<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Qarium\Model;

class City extends Model
{
    protected int       $cityID;
    protected string    $name;
    protected int       $countryID;

    protected ?Country   $iCountry = null;

    public static function getTableName(): string
    {// 2023-06-12
        return 'Cities';
    }

    public static function getTableIDs(): array
    {// 2023-06-12
        return ['cityID'];
    }

    public static function getRelationMappings(): array
    {// 2023-06-12
        $relations =
        [
            'iCountry' =>
            [
                'relation'   => Model::HAS_ONE_RELATION,
                'modelClass' => Country::class,
                'join'       =>
                [
                    'from' => 'Cities.countryID',
                    'to'   => 'Countries.countryID'
                ]
            ]
        ];

        return $relations;
    }
}
