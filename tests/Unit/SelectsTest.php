<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;

use Tests\Support\Person;
use Tests\Support\School;
use Tests\Support\City;

class SelectsTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected static function getClient(): Client
    {// 2023-06-15
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            ->host('127.0.0.1')
            ->port('3306')
            ->user('user')
            ->password('password')
            ->database('db')
            ->charset('utf8mb4');

        $iClient = Client::create($iConfig); // MySQL client

        return $iClient;
    }

    /**
     * Method is called before test file run
     */
    protected function _before(): void
    {// 2023-06-15
    }

    /**
     * @return array<string, array<int, ModelQueryBuilder|array>>
     */
    public function caseProvider(): array
    {// 2023-05-16
        // Providers are run before anything else, so we are initalizing the client here.
        Objection::setClient(self::getClient());

        $cases = [];

        $cases['With graph joined belongs to one relation'] = function()
        {
            $case =
            [
                School::query()
                    ->withGraphJoined('iCity')
                    ->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Schools`.`schoolID` AS `schoolID`, `Schools`.`name` AS `name`, `Schools`.`cityID` AS `cityID`, `iCity`.`cityID` AS `iCity:cityID`, `iCity`.`countryID` AS `iCity:countryID`, `iCity`.`name` AS `iCity:name` FROM `Schools` LEFT JOIN `Cities` AS `iCity` ON(`iCity`.`cityID` = `Schools`.`cityID`) WHERE `Schools`.`schoolID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['With graph joined has many relation'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren')
                    ->findByID(3),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[3]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['With graph joined has one relation'] = function()
        {
            $case =
            [
                City::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Cities`.`cityID` AS `cityID`, `Cities`.`countryID` AS `countryID`, `Cities`.`name` AS `name`, `iCountry`.`countryID` AS `iCountry:countryID`, `iCountry`.`name` AS `iCountry:name`, `iCountry`.`ISOCode2` AS `iCountry:ISOCode2`, `iCountry`.`ISOCode3` AS `iCountry:ISOCode3` FROM `Cities` LEFT JOIN `Countries` AS `iCountry` ON(`iCountry`.`countryID` = `Cities`.`countryID`) WHERE `Cities`.`cityID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['With graph joined many to many relation'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iSchools')
                    ->findByID(3),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iSchools`.`schoolID` AS `iSchools:schoolID`, `iSchools`.`name` AS `iSchools:name`, `iSchools`.`cityID` AS `iSchools:cityID` FROM `Persons` LEFT JOIN `PersonsToSchools` AS `iSchools_through` ON(`iSchools_through`.`personID` = `Persons`.`personID`) LEFT JOIN `Schools` AS `iSchools` ON(`iSchools`.`schoolID` = `iSchools_through`.`schoolID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[3]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['With graph joined has one relation'] = function()
        {
            $case =
            [
                City::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Cities`.`cityID` AS `cityID`, `Cities`.`countryID` AS `countryID`, `Cities`.`name` AS `name`, `iCountry`.`countryID` AS `iCountry:countryID`, `iCountry`.`name` AS `iCountry:name`, `iCountry`.`ISOCode2` AS `iCountry:ISOCode2`, `iCountry`.`ISOCode3` AS `iCountry:ISOCode3` FROM `Cities` LEFT JOIN `Countries` AS `iCountry` ON(`iCountry`.`countryID` = `Cities`.`countryID`) WHERE `Cities`.`cityID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['With graph joined has one through relation'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(3),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iCountry`.`countryID` AS `iCountry:countryID`, `iCountry`.`name` AS `iCountry:name`, `iCountry`.`ISOCode2` AS `iCountry:ISOCode2`, `iCountry`.`ISOCode3` AS `iCountry:ISOCode3` FROM `Persons` LEFT JOIN `PersonsToCountries` AS `iCountry_through` ON(`iCountry_through`.`personID` = `Persons`.`personID`) LEFT JOIN `Countries` AS `iCountry` ON(`iCountry`.`countryID` = `iCountry_through`.`countryID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[3]
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Basic find all'] = function()
        {
            $case =
            [
                Person::query(),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT * FROM `Persons`',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic find by ID'] = function()
        {
            $case =
            [
                Person::query()->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT * FROM `Persons` WHERE `Persons`.`personID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren'),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined, left join'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren', ['joinOperation'=>'leftJoin']),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined, right join'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren', ['joinOperation'=>'rightJoin']),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` RIGHT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined, inner join'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren', ['joinOperation'=>'innerJoin']),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` INNER JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined, outer join'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren', ['joinOperation'=>'outerJoin']),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` OUTER JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        // TODO: Implement this
        // $cases['Basic all with graph joined, aliases'] = function()
        // {
        //     $case =
        //     [
        //         Person::query()
        //             ->withGraphJoined('iChildren', ['aliases'=>['iChildren'=>'children']]),
        //         [
        //             'mysql'=>
        //             [
        //                 'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `children`.`personID` AS `children:personID`, `children`.`name` AS `children:name`, `children`.`parentID` AS `children:parentID` FROM `Persons` LEFT JOIN `Persons` AS `children` ON(`children`.`personID` = `Persons`.`parentID`)',
        //                 'bindings'=>[]
        //             ]
        //         ]
        //     ];
        //
        //     return $case;
        // };

        $cases['Basic with graph joined, find by id'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren')
                    ->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined multiple same table'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren')
                    ->withGraphJoined('iParents'),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID`, `iParents`.`personID` AS `iParents:personID`, `iParents`.`name` AS `iParents:name`, `iParents`.`parentID` AS `iParents:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) LEFT JOIN `Persons` AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined multiple same table, find by ID'] = function()
        {
            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren')
                    ->withGraphJoined('iParents')
                    ->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID`, `iParents`.`personID` AS `iParents:personID`, `iParents`.`name` AS `iParents:name`, `iParents`.`parentID` AS `iParents:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) LEFT JOIN `Persons` AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];

            return $case;
        };


        $cases['With graph joined one level deep'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iParents.iChildren'),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iParents`.`personID` AS `iParents:personID`, `iParents`.`name` AS `iParents:name`, `iParents`.`parentID` AS `iParents:parentID`, `iParents:iChildren`.`personID` AS `iParents:iChildren:personID`, `iParents:iChildren`.`name` AS `iParents:iChildren:name`, `iParents:iChildren`.`parentID` AS `iParents:iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) LEFT JOIN `Persons` AS `iParents:iChildren` ON(`iParents:iChildren`.`personID` = `iParents`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['With graph joined one level deep, find by ID'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iParents.iChildren')->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iParents`.`personID` AS `iParents:personID`, `iParents`.`name` AS `iParents:name`, `iParents`.`parentID` AS `iParents:parentID`, `iParents:iChildren`.`personID` AS `iParents:iChildren:personID`, `iParents:iChildren`.`name` AS `iParents:iChildren:name`, `iParents:iChildren`.`parentID` AS `iParents:iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) LEFT JOIN `Persons` AS `iParents:iChildren` ON(`iParents:iChildren`.`personID` = `iParents`.`parentID`) WHERE `Persons`.`personID` = ?',
                        'bindings'=>[1]
                    ]
                ]
            ];

            return $case;
        };
    

        foreach($cases as $name=>$caseFn)
        {
            $cases[$name] = $caseFn();
        }

        return $cases;
    }

	/**
	 * @param array<string, array<string, string>> $iExpected
	 * @dataProvider caseProvider
	 */
    public function testQueryBuilder(ModelQueryBuilder $iQueryBuilder, array $iExpected): void
    {
        $iQueryBuilder->preCompile();
        $iQueryCompiler = new QueryCompiler($iQueryBuilder->getClient(), $iQueryBuilder, []);

        $iQuery = $iQueryCompiler->toSQL();

        // $test = array_diff_assoc(str_split($iExpected['mysql']['sql']), str_split($iQuery->getSQL()));
        //
        // if(count($test) > 0)
        // {
        //     codecept_debug($test);
        // }

        $sqlAndBindings =
        [
            'sql'=>$iQuery->getSQL(),
            'bindings'=>$iQuery->getBindings()
        ];

        $this->assertSame($iExpected['mysql'], $sqlAndBindings);
    }
}
