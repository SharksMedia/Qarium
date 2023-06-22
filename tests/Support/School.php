<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Objection\Model;

class School extends Model
{
    protected int       $schoolID;
    protected string    $name;

    protected ?array    $iStudents;

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
            'iStudents'=>
            [
                'relation'=>Model::MANY_TO_MANY_RELATION,
                'modelClass'=>Student::class,
                'join'=>
                [
                    'from'=>'Schools.schoolID',
                    'through'=>
                    [
                        'from'=>'PersonsToSchools.schoolID',
                        'to'=>'PersonsToSchools.schoolID',
                        'extras'=>['role'],
                    ],
                    'to'=>'Persons.personID'
                ]
            ]
        ];

        return $relations;
    }
}
