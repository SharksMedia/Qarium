<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Qarium\Model;

class School extends Model
{
    protected int       $schoolID;
    protected string    $name;
    protected int       $cityID;

    protected ?array    $iPersons;

    protected ?City     $iCity;

    public static function getTableName(): string
    {// 2023-06-12
        return 'Schools';
    }

    public static function getTableIDs(): array
    {// 2023-06-12
        return ['schoolID'];
    }

    public static function getRelationMappings(): array
    {// 2023-06-12
        $relations =
        [
            'iPersons' =>
            [
                'relation'   => Model::MANY_TO_MANY_RELATION,
                'modelClass' => Student::class,
                'join'       =>
                [
                    'from'    => 'Schools.schoolID',
                    'through' =>
                    [
                        'from'   => 'PersonsToSchools.schoolID',
                        'to'     => 'PersonsToSchools.schoolID',
                        'extras' => ['role'],
                    ],
                    'to' => 'Persons.personID'
                ]
            ],
            'iCity' =>
            [
                'relation'   => Model::BELONGS_TO_ONE_RELATION,
                'modelClass' => City::class,
                'join'       =>
                [
                    'from' => 'Schools.cityID',
                    'to'   => 'Cities.cityID'
                ]
            ]
        ];

        return $relations;
    }
}
