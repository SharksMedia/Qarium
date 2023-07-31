<?php


namespace Tests\Unit;

use Sharksmedia\QueryBuilder\Query;
use Tests\Support\ModelTester;
use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;

use Tests\Support\Person;

class InsertsTest extends \Codeception\Test\Unit
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

        $cases['Insert single person'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 6;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;
            
            $case =
            [
                Person::query()
                    ->insert(['name'=>'Magnus', 'parentID'=>null]),
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `Persons` (`name`, `parentID`) VALUES (?, NULL)',
                        'bindings'=>['Magnus'],
                        'expected'=>$iPerson->getObject(),
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Insert and fetch single person'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 6;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $case =
            [
                Person::query()
                    ->insertAndFetch(['name'=>'Magnus', 'parentID'=>null]),
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `Persons` (`name`, `parentID`) VALUES (?, NULL)',
                        'bindings'=>['Magnus'],
                        'expected'=>$iPerson->getObject(),
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Insert from instance relatedQuery'] = function()
        {
            $iMTPerson = ModelTester::create(Person::class);
            $iMTPerson->personID = 5;
            $iMTPerson->name = 'Magnus';
            $iMTPerson->parentID = null;
            $iPerson = $iMTPerson->getObject();

            $query = $iPerson->_relatedQuery('iChildren')
                ->insert(['name'=>'Fluffy']);

            $iMTChild = ModelTester::create(Person::class);
            $iMTChild->personID = 6;
            $iMTChild->name = 'Fluffy';
            $iMTChild->parentID = 5;
            $iChild = $iMTChild->getObject();
            
            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `Persons` (`name`, `parentID`) VALUES (?, ?)',
                        'bindings'=>['Fluffy', 5],
                        'expected'=>$iChild,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Insert from static relatedQuery'] = function()
        {

            $query = Person::relatedQuery('iChildren')
                ->for(5)
                ->insert(['name'=>'Fluffy']);

            $iMTChild = ModelTester::create(Person::class);
            $iMTChild->personID = 6;
            $iMTChild->name = 'Fluffy';
            $iMTChild->parentID = 5;
            $iChild = $iMTChild->getObject();
            
            $case =
            [
                $query,
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `Persons` (`name`, `parentID`) VALUES (?, ?)',
                        'bindings'=>['Fluffy', 5],
                        'expected'=>$iChild,
                    ]
                ]
            ];
            
            return $case;
        };

        $cases['Insert from instance through relation tabel with extra data'] = function()
        {

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

        $cases['Insert multiple persons'] =
        [
            function()
            {
                // Mysql does not return the id of every row inserted, so we can't allow multiple inserts. Use insertAndFetch instead, where we fetch the rows afterwards.
                $persons =
                [
                    ['name'=>'Magnus', 'parentID'=>null],
                    ['name'=>'Jennifer', 'parentID'=>null]
                ];

                Person::query()
                    ->insert($persons);
            },
            [
                'error'=>new \BadFunctionCallException('Inserting multiple rows is not supported.')
            ]
        ];

        $cases['Insert and fetch multiple persons'] =
        [
            function()
            {
                // Mysql does not return the id of every row inserted, so we can't allow multiple inserts. Use insertAndFetch instead, where we fetch the rows afterwards.
                $persons =
                [
                    ['name'=>'Magnus', 'parentID'=>null],
                    ['name'=>'Jennifer', 'parentID'=>null]
                ];

                Person::query()
                    ->insertAndFetch($persons);
            },
            [
                'error'=>new \BadFunctionCallException('Inserting multiple rows is not supported.')
            ]
        ];

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
