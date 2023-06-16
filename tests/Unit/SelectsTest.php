<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;
use Tests\Support\Person;

class SelectsTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected static function getClient(): Client
    {// 2023-06-15
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            // ->host('172.21.74.3')
            ->host('127.0.0.1')
            // ->port('3306')
            ->port('5060')
            ->user('bluemedico_admin')
            ->password('926689c103aeb7b7')
            ->database('ObjectionTest')
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

        $cases['Basic find all'] = function()
        {
            $case =
            [
                Person::query(),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID` FROM `Persons`',
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
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID` FROM `Persons` WHERE `Persons`.`personID` = ? LIMIT ?',
                        'bindings'=>[1, 1]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iChildren'),
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
                Person::query()->withGraphJoined('iChildren', ['joinOperation'=>'leftJoin']),
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
                Person::query()->withGraphJoined('iChildren', ['joinOperation'=>'rightJoin']),
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
                Person::query()->withGraphJoined('iChildren', ['joinOperation'=>'innerJoin']),
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
                Person::query()->withGraphJoined('iChildren', ['joinOperation'=>'outerJoin']),
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

        $cases['Basic all with graph joined, aliases'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iChildren', ['aliases'=>['iChildren'=>'children']]),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `children`.`personID` AS `children:personID`, `children`.`name` AS `children:name`, `children`.`parentID` AS `children:parentID` FROM `Persons` LEFT JOIN `Persons` AS `children` ON(`children`.`personID` = `Persons`.`parentID`)',
                        'bindings'=>[]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic with graph joined, find by id'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iChildren')->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) WHERE `Persons`.`personID` = ? LIMIT ?',
                        'bindings'=>[1, 1]
                    ]
                ]
            ];

            return $case;
        };

        $cases['Basic all with graph joined multiple same table'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iChildren')->withGraphJoined('iParents'),
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
                Person::query()->withGraphJoined('iChildren')->withGraphJoined('iParents')->findByID(1),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID`, `iParents`.`personID` AS `iParents:personID`, `iParents`.`name` AS `iParents:name`, `iParents`.`parentID` AS `iParents:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`personID` = `Persons`.`parentID`) LEFT JOIN `Persons` AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) WHERE `Persons`.`personID` = ? LIMIT ?',
                        'bindings'=>[1, 1]
                    ]
                ]
            ];

            return $case;
        };
/*
        $cases['With graph joined one level deep'] = function()
        {
            $case =
            [
                Person::query()->withGraphJoined('iParents.iChildren'),
                [
                    'mysql'=>
                    [
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID`, `iChildren:iParents`.`personID` AS `iChildren:iParents:personID`, `iChildren:iParents`.`name` AS `iChildren:iParents:name`, `iChildren:iParents`.`parentID` AS `iChildren:iParents:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON(`iChildren`.`parentID` = `Persons`.`personID`) LEFT JOIN `Persons` AS `iChildren:iParents` ON(`iChildren:iParents`.`personID` = `iChildren`.`parentID`)',
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
                        'sql'=>'SELECT `Persons`.`personID` AS `personID`, `Persons`.`name` AS `name`, `Persons`.`parentID` AS `parentID`, `iChildren`.`personID` AS `iChildren:personID`, `iChildren`.`name` AS `iChildren:name`, `iChildren`.`parentID` AS `iChildren:parentID`, `iChildren:iParents`.`personID` AS `iChildren:iParents:personID`, `iChildren:iParents`.`name` AS `iChildren:iParents:name`, `iChildren:iParents`.`parentID` AS `iChildren:iParents:parentID` FROM `Persons` LEFT JOIN `Persons` AS `iChildren` ON `iChildren`.`parentID` = `Persons`.`personID` LEFT JOIN `Persons` AS `iChildren:iParents` ON `iChildren:iParents`.`personID` = `iChildren`.`parentID` WHERE `Persons`.`personID` = 1',
                        'bindings'=>[1]
                    ]
                ]
            ];

            return $case;
        };
*/
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
        $iQueryCompiler = new QueryCompiler($iQueryBuilder->getClient(), $iQueryBuilder, []);

        $iQuery = $iQueryCompiler->toSQL();

        $sqlAndBindings =
        [
            'sql'=>$iQuery->getSQL(),
            'bindings'=>$iQuery->getBindings()
        ];

        $this->assertSame($iExpected['mysql'], $sqlAndBindings);
    }
}
