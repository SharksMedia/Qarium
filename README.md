# Objection
A objectionjs inspired ORM for PHP

### Usage
```php
<?php
// src/index.php

namespace Mmpa\TestQarium;

require_once __DIR__.'/../vendor/autoload.php';

use \Sharksmedia\SharQ\Config;
use \Sharksmedia\SharQ\Client;

use \Sharksmedia\Qarium\Qarium;

function getClient(): Client
{
    $iConfig = (new Config(Client::TYPE_MYSQL))
        ->host('0.0.0.0')
        ->port('3306')
        ->user('user')
        ->password('password')
        ->database('db')
        ->charset('utf8mb4');

    $iConfig->timeout(5);

    $iClient = Client::create($iConfig);

    return $iClient;
}

// Create the client
$iClient = getClient();

// Iinitalize the driver
$iClient->initializeDriver();

// Set the client for Objection
Qarium::setClient($iClient);

// Find a person by ID
// SELECT `Persons`.* FROM `Persons` WHERE `Persons`.`personID` = ?
$iPerson = Person::query()->findById(1)->run();

// Query a person with relations joined
// SELECT `Persons`.`personID` AS `personID`, `iPets`.`petID` AS `iPets:petID`, `iPets`.`ownerID` AS `iPets:ownerID` FROM `Persons` LEFT JOIN `Pets` AS `iPets` ON(`iPets`.`ownerID` = `Persons`.`personID`) WHERE `name` = ?
$iPerson = Person::query()
    ->withGraphJoined('iPets')
    ->findById(1)
    ->run();


// Get a relation of an already fetched model
// SELECT `Pets`.* FROM `Pets` WHERE `Pets`.`ownerID` IN(?)
$iPets = $iPerson->lrelatedQuery('iPets')->run();

// Query a relation from a model
// SELECT `Pets`.* FROM `Pets` WHERE `Pets`.`ownerID` IN(?) AND `species` = ? ORDER BY `name` ASC
$iDogs = Person::relatedQuery('iPets')
    ->for(1) // For person with ID 1
    ->where('species', 'Dog')
    ->orderBy('name', 'ASC')
    ->run();

// Query a relation from a model with a where clause on the relation
// SELECT `Persons`.`personID` AS `personID`, `iParents`.`personID` AS `iParents:personID`, `iPets`.`petID` AS `iPets:petID`, `iPets`.`ownerID` AS `iPets:ownerID`, `iPets`.`name` AS `iPets:name`, `iPets`.`species` AS `iPets:species` FROM `Persons` LEFT JOIN (SELECT `Persons`.* FROM `Persons` WHERE `name` = ?) AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) LEFT JOIN `Pets` AS `iPets` ON(`iPets`.`ownerID` = `Persons`.`personID`) WHERE `Persons`.`personID` = ?
$iPerson = Person::query()
    ->withGraphJoined('[iParents, iPets]')
    ->modifyGraph('iParents', function ($iParentsQuery) {
        $iParentsQuery->where('name', 'John');
    })
    ->findById(1)
    ->run();
```

### Installation
Add Sharksmedia repository
```bash
composer config repositories.sharksmedia/sharq vcs git@github.com:SharkMagnus/SharQ.git
composer config repositories.sharksmedia/qarium vcs git@github.com:SharkMagnus/Qarium.git
```

Require Qarium
```bash
composer require sharksmedia/qarium:master
```
