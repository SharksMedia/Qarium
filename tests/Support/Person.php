<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Objection\Model;

class Person extends Model
{
    protected int       $personID;
    protected ?int      $parentID;
    protected string    $name;

    /**
     * 2023-06-12
     * @var array<int, Person>
     */
    protected array     $iChildren = [];
    protected array     $iParents = [];
    protected array     $iSchools = [];

    protected ?Country  $iCountry = null;

    public static function getTableName(): string
    {// 2023-06-12
        return 'Persons';
    }

    public static function getTableIDs(): array
    {// 2023-06-12
        return ['personID'];
    }

    // public function __construct(array $data)
    // {
    //     foreach($data as $columnName=>$columnValue)
    //     {
    //         $this->{$columnName} = $columnValue;
    //     }
    // }

    public static function getRelationMappings(): array
    {// 2023-06-12
        $relations =
        [
            'iChildren'=>
            [
                'relation'=>Model::HAS_MANY_RELATION,
                'modelClass'=>Person::class,
                'join'=>
                [
                    'from'=>'Persons.parentID',
                    'to'=>'Persons.personID'
                ]
            ],
            'iParents'=>
            [
                'relation'=>Model::HAS_MANY_RELATION,
                'modelClass'=>Person::class,
                'join'=>
                [
                    'from'=>'Persons.personID',
                    'to'=>'Persons.parentID'
                ]
            ],
            'iSchools'=>
            [
                'relation'=>Model::MANY_TO_MANY_RELATION,
                'modelClass'=>School::class,
                'join'=>
                [
                    'from'=>'Persons.personID',
                    'through'=>
                    [
                        'from'=>'PersonsToSchools.personID',
                        'to'=>'PersonsToSchools.schoolID',
                        'extras'=>['role'],
                    ],
                    'to'=>'Schools.schoolID'
                ]
            ],
            'iCountry'=>
            [
                'relation'=>Model::HAS_ONE_THROUGH_RELATION,
                'modelClass'=>Country::class,
                'join'=>
                [
                    'from'=>'Persons.personID',
                    'through'=>
                    [
                        'from'=>'PersonsToCountries.personID',
                        'to'=>'PersonsToCountries.countryID',
                    ],
                    'to'=>'Countries.countryID'
                ]
            ],
        ];

        return $relations;
    }
}
