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
                    'from'=>'Person.parentID',
                    'to'=>'Person.personID'
                ]
            ],
            'iParents'=>
            [
                'relation'=>Model::HAS_MANY_RELATION,
                'modelClass'=>Person::class,
                'join'=>
                [
                    'from'=>'Person.personID',
                    'to'=>'Person.parentID'
                ]
            ]
        ];

        return $relations;
    }
}
