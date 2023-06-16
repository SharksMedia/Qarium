<?php

declare(strict_types=1);

// 2023-06-12

require_once __DIR__ . '/../vendor/autoload.php';

use \Sharksmedia\QueryBuilder\Config;
use \Sharksmedia\QueryBuilder\Client;
use \Sharksmedia\Objection\Objection;
use \Sharksmedia\Objection\Model;

$iConfig = (new Config(Config::CLIENT_MYSQL))
    ->host('127.0.0.1')
    // ->port('3306')
    ->port(5060)
    ->user('bluemedico_admin')
    ->password('926689c103aeb7b7')
    ->database('ObjectionTest')
    ->charset('utf8mb4');

$iClient = Client::create($iConfig);

$iClient->initializeDriver();

Objection::setClient($iClient);

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

    /**
     * 2023-06-12
     * @var array<int, Person>
     */
    protected array     $iParents = [];

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
                    'from'=>'Person.personID',
                    'to'=>'Person.parentID'
                ]
            ],
            'iParents'=>
            [
                'relation'=>Model::HAS_MANY_RELATION,
                'modelClass'=>Person::class,
                'join'=>
                [
                    'from'=>'Person.parentID',
                    'to'=>'Person.personID'
                ]
            ]
        ];

        return $relations;
    }
}

$iPerson = Person::query()
    ->withGraphJoined('iChildren')
    ->withGraphJoined('iParents')
    ->where('Persons.personID', 3);

$iPersons = $iPerson->run();

print_r($iPersons);

//
// $trx = Person::startTransaction();
//
// $iPerson = Person::query($trx)
//     ->where('Person.personID', 1)
//     ->first()
//     ->run();
//
// $iChild = Person::query($trx)
//     ->insert(['name'=>'Child', 'parentID'=>$iPerson->personID])
//     ->run();
//
// $trx->commit();
