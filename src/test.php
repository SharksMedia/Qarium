<?php

declare(strict_types=1);

// 2023-06-12

require_once __DIR__ . '/vendor/autoload.php';

use Sharkemedia\Database\Config;
use Sharksmedia\Database\Database;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\Model;

$iConfig = (new Config(Config::CLIENT_MYSQL))
    ->host('127.0.0.1')
    ->port('3306')
    ->user('foo')
    ->password('bar')
    ->database('baz_live')
    ->charset('utf8');

$iDatabase = Database::createFromConfig($iConfig);

Objection::setDatabase($iDatabase);

class Person extends Model
{
    public static function getTableName(): string
    {// 2023-06-12
        return 'Person';
    }

    public static function getTableIDs(): array
    {// 2023-06-12
        return ['personID'];
    }

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
            ]
        ];

        return $relations;
    }
}

$trx = Person::startTransaction();

$iPerson = Person::query($trx)
    ->where('Person.personID', 1)
    ->first()
    ->run();

$iChild = Person::query($trx)
    ->insert(['name'=>'Child', 'parentID'=>$iPerson->personID])
    ->run();

$trx->commit();
