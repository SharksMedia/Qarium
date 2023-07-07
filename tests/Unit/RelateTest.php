<?php

namespace Tests\Unit;

use Sharksmedia\QueryBuilder\Query;
use Tests\Support\ModelTester;
use Tests\Support\School;
use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;

use Tests\Support\Person;

class RelateTest extends \Codeception\Test\Unit
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
     * @param string $method
     * @param string $sql
     * @param array $bindings
     * @return Query
     */
    protected static function createQuery(string $method, string $sql, array $bindings=[]): Query
    {
        $iQuery = new Query($method, [], 10000, false, $bindings, (string)rand(0, 10000));
        $iQuery->setSQL($sql);

        return $iQuery;
    }

    /**
     * Method is called before test file run
     */
    protected function _before(): void
    {// 2023-06-15
        $maxPersonID = 5;
        
        $sql = 'DELETE FROM `Persons` WHERE `personID` > ?;';
        $iQuery = self::createQuery('DELETE', $sql, [$maxPersonID]);

        $iClient = self::getClient();

        if(!$iClient->isInitialized()) $iClient->initializeDriver();

        $iClient->query($iQuery);

        $sql = 'ALTER TABLE `Persons` AUTO_INCREMENT=?;';
        $iQuery = self::createQuery('ALTER', $sql, [$maxPersonID]);

        $iClient->query($iQuery);

        // FIXME: Cleanup the database
    }

    protected function _after(): void
    {// 2023-06-15
        $this->_before();
        // FIXME: Cleanup the database
    }

    /**
     * @return array<string, array<int, ModelQueryBuilder|array>>
     */
    public function caseProvider(): array
    {
        // Providers are run before anything else, so we are initalizing the client here.
        Objection::setClient(self::getClient());

        $cases = [];

        $cases['Relate single object with extra data'] = function()
        {
            $iMTPerson = ModelTester::create(Person::class);
            $iMTPerson->personID = 3;
            $iMTPerson->name = 'Magnus';
            $iMTPerson->parentID = null;
            $iMTPerson->role = 'student';
            $iPerson = $iMTPerson->getObject();

            $iMTSchool = ModelTester::create(School::class);
            $iMTSchool->schoolID = 1;
            $iMTSchool->name = 'SharkCamp';
            $iMTSchool->cityID = 1;
            $iSchool = $iMTSchool->getObject();

            $query = $iPerson->_relatedQuery('iSchools')->relate($iSchool);
            
            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `PersonsToSchools` (`parentID`, `schoolID`,  `role`) VALUES (?, ?, ?)',
                        'bindings'=>[3, 1, 'student'],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Relate multiple objects with extra data'] = function()
        {
            $iMTPerson = ModelTester::create(Person::class);
            $iMTPerson->personID = 3;
            $iMTPerson->name = 'Magnus';
            $iMTPerson->parentID = null;
            $iMTPerson->role = 'student';
            $iPerson = $iMTPerson->getObject();

            $iMTSchool = ModelTester::create(School::class);
            $iMTSchool->schoolID = 1;
            $iMTSchool->name = 'SharkCamp';
            $iMTSchool->cityID = 1;
            $iSchool1 = $iMTSchool->getObject();

            $iMTSchool = ModelTester::create(School::class);
            $iMTSchool->schoolID = 2;
            $iMTSchool->name = 'WhaleCamp';
            $iMTSchool->cityID = 1;
            $iSchool2 = $iMTSchool->getObject();

            $query = $iPerson->_relatedQuery('iSchools')->relate([$iSchool1, $iSchool2]);
            
            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `PersonsToSchools` (`parentID`, `schoolID`,  `role`) VALUES (?, ?, ?), (?, ?, ?)',
                        'bindings'=>[3, 1, 'student', 3, 2, 'student'],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Relate single objects without extra data'] = function()
        {
            $iMTPerson = ModelTester::create(Person::class);
            $iMTPerson->personID = 3;
            $iMTPerson->name = 'Magnus';
            $iMTPerson->parentID = null;
            $iMTPerson->role = 'student';
            $iPerson = $iMTPerson->getObject();

            $iMTCountry = ModelTester::create(Country::class);
            $iMTCountry->countryID = 1;
            $iMTCountry->name = 'Sweden';
            $iCountry1 = $iMTCountry->getObject();

            $query = $iPerson->_relatedQuery('iCountry')->relate($iCountry1);
            
            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `PersonsToCountries` (`personID`, `countryID`) VALUES (?, ?)',
                        'bindings'=>[3, 1],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Relate single array with extra data from static function'] = function()
        {
            $query = Person::relatedQuery('iSchools')
                ->for(3)
                ->relate([1, 'student']);

            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `PersonsToSchools` (`parentID`, `schoolID`,  `role`) VALUES (?, ?, ?)',
                        'bindings'=>[3, 1, 'student'],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Relate multiple array with extra data from static function'] = function()
        {
            $query = Person::relatedQuery('iSchools')
                ->for(3)
                ->relate([[1, 'student'], [2, 'student']]);

            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `PersonsToSchools` (`parentID`, `schoolID`,  `role`) VALUES (?, ?, ?), (?, ?, ?)',
                        'bindings'=>[3, 1, 'student', 3, 2, 'student'],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Unrelate from object'] = function()
        {
            $iMTPerson = ModelTester::create(Person::class);
            $iMTPerson->personID = 3;
            $iMTPerson->name = 'Magnus';
            $iMTPerson->parentID = null;
            $iMTPerson->role = 'student';
            $iPerson = $iMTPerson->getObject();

            $query = $iPerson->_relatedQuery('iSchools')
                ->unrelate()
                ->where('schoolID', 1);

            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'DELETE FROM `PersonsToSchools` WHERE `parentID` = ? AND `schoolID` IN(SELECT `schoolID` FROM `Schools` WHERE `schoolID` = ?)',
                        'bindings'=>[3, 1],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;

        };

        $cases['Unrelate with static function'] = function()
        {
            $query = Person::relatedQuery('iSchools')
                ->for(3)
                ->unrelate()
                ->where('schoolID', 1);

            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'DELETE FROM `PersonsToSchools` WHERE `parentID` = ? AND `schoolID` IN(SELECT `schoolID` FROM `Schools` WHERE `schoolID` = ?)',
                        'bindings'=>[3, 1],
                        'expected'=>true,
                    ]
                ]
            ];
            
            return $case;

        };

        foreach($cases as $caseName=>$case)
        {
            $cases[$caseName] = $case();
        }

        return $cases;
    }

    public function errorCaseProvider(): array
    {// 2023-05-16
        // Providers are run before anything else, so we are initalizing the client here.
        Objection::setClient(self::getClient());

        $cases = [];

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

        $expectedSqlAndBindings =
        [
            'sql'=>$iExpected['mysql']['sql'],
            'bindings'=>$iExpected['mysql']['bindings'],
        ];

        $this->assertSame($expectedSqlAndBindings, $sqlAndBindings);

        if(!$iQueryBuilder->getClient()->isInitialized()) $iQueryBuilder->getClient()->initializeDriver();

        $result = $iQueryBuilder->run();

        $this->assertSame($iExpected['mysql']['expected'], $result);
    }

	/**
     * @param callable $buildQueryCallback
	 * @param array<string, mixed> $iExpected
	 * @dataProvider errorCaseProvider
	 */
    public function testQueryBuilderBuildErrors(callable $buildQueryCallback, array $iExpected): void
    {
        try
        {
            $buildQueryCallback();

            $this->fail('Expected exception');
        }
        catch(\Exception $exception)
        {
            $this->assertSame($iExpected['error']->getMessage(), $exception->getMessage());
        }
    }
}
