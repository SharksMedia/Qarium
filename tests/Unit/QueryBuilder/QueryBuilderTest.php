<?php

declare(strict_types=1);

namespace Tests\Unit\QueryBuilder;

use Codeception\Test\Unit;
use Sharksmedia\Objection\Exceptions\ModifierNotFoundError;
use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilderBase;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\Operations\FindOperation;
use Sharksmedia\Objection\ReferenceBuilder;
use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Client\MySQL;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Query;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Tests\Support\MockMySQLClient;
use Tests\Support\MockPDOStatement;

class QueryBuilderTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected array $mockQueryResults = [];
    protected array $executedQueries = [];

    protected function _before()
    {
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            ->host('127.0.0.1')
            ->port(3306)
            ->user('user')
            ->password('password')
            ->database('db')
            ->charset('utf8mb4');

        $iTempClient = new MySQL($iConfig);

        $iClient = new MockMySQLClient($iConfig, function(Query $iQuery, array $options) use($iTempClient)
        {
            $sql = $iQuery->getSQL();
            $bindings = $iQuery->getBindings();

            $dsn = $iTempClient->createDSN();

            $iPDOStatement = new MockPDOStatement();

            $iPDOStatement->setResults($this->mockQueryResults);

            $this->executedQueries[] = $sql;

            return $iPDOStatement;
        });
        //
        // $iClient = Client::create($iConfig);

        $iClient->initializeDriver();

        Objection::setClient($iClient);

        $iQueryBuilder = new QueryBuilder($iClient, 'db');

        Model::setQueryBuilder($iQueryBuilder);
        // You may initialize objects here if necessary
    }

    protected function _after()
    {
        // This method will run after each test
    }

    public static function ref(string $expression): ReferenceBuilder
    {
        return new ReferenceBuilder($expression);
    }

    public static function createFindOperation(ModelQueryBuilder $iBuilder, array $whereObj): FindOperation
    {
        $findOperationFactory = $iBuilder->getFindOperationFactory();

        $iFindOperation = $findOperationFactory($iBuilder);

        $iFindOperation->onBefore2 = \Closure::bind(function()
        {

        }, $iFindOperation);

        $iFindOperation->onAfter2 = \Closure::bind(function()
        {

        }, $iFindOperation);

        $iFindOperation->onBuildQueryBuilder = \Closure::bind(function(ModelQueryBuilder $iBuilder, $iQueryBuilder) use($whereObj)
        {
            return $iQueryBuilder->where(...$whereObj);
        }, $iFindOperation);

        return $iFindOperation;
    }

    // Tests
    public function testShouldHaveKnexMethods(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };
        
        $ignoreMethods =
        [
            'setMaxListeners',
            'getMaxListeners',
            'emit',
            'addListener',
            'on',
            'prependListener',
            'once',
            'prependOnceListener',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'listenerCount',
            'eventNames',
            'rawListeners',
            'pluck', // not supported anymore in objection v3+
            'queryBuilder', // this method is added to the knex mock, but should not available on objection's QueryBuilder
            'raw', // this method is added to the knex mock, but should not available on objection's QueryBuilder

            'getClient',
            'getSchema',
            'getSelectMethod',
            'getMethod',
            'getSingle',
            'getStatements',
            'hasAlias',
            'fetchMode',

        ];

        $builder = ModelQueryBuilder::forClass($TestModel::class);

        // $missingFunctions = [];

        $queryBuilderMethods = get_class_methods(QueryBuilder::class);
        foreach($queryBuilderMethods as $qbMethodName)
        {
            if(!in_array($qbMethodName, $ignoreMethods, true))
            {
                // if(!method_exists($builder, $qbMethodName)) $missingFunctions[] = $qbMethodName;

                $this->assertTrue(method_exists($builder, $qbMethodName), "QueryBuilder method '" . $qbMethodName . "' is missing from ModelQueryBuilder");
            }
        }

        // codecept_debug($missingFunctions);
        //
        // $this->assertTrue(count($missingFunctions) === 0, "QueryBuilder methoth is missing from ModelQueryBuilder");

    }

    public function testModelClassShouldReturnTheModelClass(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };

        $this->assertEquals($TestModel::class, ModelQueryBuilder::forClass($TestModel::class)->getModelClass());
    }

    public function testModifyShouldExecuteTheGivenFunctionAndPassTheBuilderToIt(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };

        $builder = ModelQueryBuilder::forClass($TestModel::class);
        $called = false;

        $builder->modify(function($b) use ($builder, &$called)
        {
            $called = true;
            $this->assertSame($builder, $b);
        });

        $this->assertTrue($called);
    }

    public function testShouldBeAbleToPassArgumentsToModify(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };

        $builder = ModelQueryBuilder::forClass($TestModel::class);
        $called1 = false;
        $called2 = false;

        // Should accept a single function.
        $builder->modify(function($query, $arg1, $arg2) use($builder, &$called1)
        {
            $called1 = true;
            $this->assertSame($builder, $query);
            $this->assertEquals('foo', $arg1);
            $this->assertEquals(1, $arg2);
        }, 'foo', 1);

        $this->assertTrue($called1);

        $called1 = false;

        // Should accept an array of functions.
        $builder->modify(
        [
            function($query, $arg1, $arg2) use($builder, &$called1)
            {
                $called1 = true;
                $this->assertSame($builder, $query);
                $this->assertEquals('foo', $arg1);
                $this->assertEquals(1, $arg2);
            },

            function($query, $arg1, $arg2) use($builder, &$called2)
            {
                $called2 = true;
                $this->assertSame($builder, $query);
                $this->assertEquals('foo', $arg1);
                $this->assertEquals(1, $arg2);
            },
        ], 'foo', 1);

        $this->assertTrue($called1);
        $this->assertTrue($called2);
    }

    public function testShouldBeAbleToPassArgumentsToModifyWhenUsingNamedModifiers(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getModifiers(): array
            {// 2023-08-02
                return
                [
                    'modifier1'=>function($query, $arg1, $arg2, $context, $markCalledFunc, &$builder)
                    {
                        $markCalledFunc();
                        $context->assertSame($builder, $query);
                        $context->assertEquals('foo', $arg1);
                        $context->assertEquals(1, $arg2);
                    },

                    'modifier2'=>function($query, $arg1, $arg2, $context, $markCalledFunc, &$builder)
                    {
                        $markCalledFunc();
                        $context->assertSame($builder, $query);
                        $context->assertEquals('foo', $arg1);
                        $context->assertEquals(1, $arg2);
                    }
                ];
            }
        };

        $called1 = false;
        $called2 = false;

        $builder = ModelQueryBuilder::forClass($TestModel::class);

        // Should accept a single modifier.
        $builder->modify('modifier1', 'foo', 1, $this, function() use(&$called1){ $called1 = true; }, $builder);
        $this->assertTrue($called1);

        // Should accept an array of modifiers.
        $builder->modify(['modifier1', 'modifier2'], 'foo', 1, $this, function() use(&$called2){ $called2 = true; }, $builder);

        $this->assertTrue($called1);
        $this->assertTrue($called2);
    }

    public function testShouldThrowIfAnUnknownModifierIsSpecified(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };

        $builder = ModelQueryBuilder::forClass($TestModel::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "unknown".');

        $builder->modify('unknown');
    }

    public function testModifyShouldDoNothingWhenReceivingUndefined(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model { };

        $builder = ModelQueryBuilder::forClass($TestModel::class);
        $res = null;

        try
        {
            $res = $builder->modify(null);
        }
        catch(\Exception $e)
        {
            $this->fail("Exception should not have been thrown");
        }

        $this->assertSame($builder, $res);
    }

    public function testModifyAcceptAListOfStringsAndCallTheCorrespondingModifiers(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model 
        {
            public static function getModifiers(): array
            {
                $a = function($qb, &$builder, $markACalledFunc, $markBCalledFunc) { $called = $qb === $builder; $markACalledFunc($called); };
                $b = function($qb, &$builder, $markACalledFunc, $markBCalledFunc) { $called = $qb === $builder; $markBCalledFunc($called); };

                $modifiers =
                [
                    'a' => $a,
                    'b' => $b,
                    // 'c' => 'a', // We do not allow string modifiers functions.
                    // 'd' => ['c', 'b']
                ];

                return $modifiers;
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class);
        $aCalled = false;
        $bCalled = false;

        $markACalled = function($called) use(&$aCalled){ $aCalled = $called; };
        $markBCalled = function($called) use(&$bCalled){ $bCalled = $called; };

        $builder->modify('a', $builder, $markACalled, $markBCalled);
        $this->assertTrue($aCalled);
        $this->assertFalse($bCalled);

        $aCalled = false;
        $bCalled = false;
        $builder->modify('b', $builder, $markACalled, $markBCalled);
        $this->assertFalse($aCalled);
        $this->assertTrue($bCalled);

        $aCalled = false;
        $bCalled = false;
        $builder->modify(['a', 'b'], $builder, $markACalled, $markBCalled);
        $this->assertTrue($aCalled);
        $this->assertTrue($bCalled);

        // $aCalled = false;
        // $bCalled = false;
        // $builder->modify([['a', [[['b']]]]]);
        // $this->assertTrue($aCalled);
        // $this->assertTrue($bCalled);
        //
        // $aCalled = false;
        // $bCalled = false;
        // $builder->modify('d');
        // $this->assertTrue($aCalled);
        // $this->assertTrue($bCalled);
    }

    public function testModifyCallsTheModifierNotFoundHookForUnknownModifiers(): void
    {
        $caughtModifiers = [];
        $TestModel = new class extends \Sharksmedia\Objection\Model 
        {
            // public static function modifierNotFound($qb, $modifier)
            // {
            //     if($qb === $builder)
            //     {
            //         $caughtModifiers[] = $modifier;
            //     }
            // }

            public static function getModifiers(): array
            {
                return
                [
                    'c' => 'a',
                    'd' => ['c', 'b']
                ];
            }

        };

        $builder = ModelQueryBuilder::forClass($TestModel::class);

        $this->expectException(ModifierNotFoundError::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "a".');

        $builder->modify('a');
        $this->assertEquals(['a'], $caughtModifiers);

        $this->expectException(ModifierNotFoundError::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "b".');

        $caughtModifiers = [];
        $builder->modify('b');
        $this->assertEquals(['b'], $caughtModifiers);

        $this->expectException(ModifierNotFoundError::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "c".');

        $caughtModifiers = [];
        $builder->modify('c');
        $this->assertEquals(['a'], $caughtModifiers);

        $this->expectException(ModifierNotFoundError::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "d".');

        $caughtModifiers = [];
        $builder->modify('d');
        $this->assertEquals(['a', 'b'], $caughtModifiers);

        $this->expectException(ModifierNotFoundError::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "d".');
    }

    public function testShouldStillThrowIfModifierNotFoundDelegateToTheDefinitionInTheSuperClass(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model 
        {
            public static function modifierNotFound(ModelQueryBuilder $iBuilder, string $modifierName): void
            {
                parent::modifierNotFound($iBuilder, $modifierName);
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "unknown".');

        $builder->modify('unknown');
    }

    // public function testShouldNotThrowIfModifierNotFoundHandlesAnUnknownModifier(): void
    // {
    //     $caughtModifier = null;
    //     $TestModel = new class extends \Sharksmedia\Objection\Model 
    //     {
    //         public static function modifierNotFound($builder, $modifier) use(&$caughtModifier) 
    //         {
    //             $caughtModifier = $modifier;
    //         }
    //     };
    //
    //     $builder = ModelQueryBuilder::forClass($TestModel::class);
    //
    //     try 
    //     {
    //         $builder->modify('unknown');
    //     } 
    //     catch (\Exception $e) 
    //     {
    //         // This exception should not be thrown, so if it is, fail the test.
    //         $this->fail('Exception was thrown when it should not have been.');
    //     }
    //
    //     $this->assertEquals('unknown', $caughtModifier);
    // }

    public function testShouldSelectAllFromTheModelTableIfNoQueryMethodsAreCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $iModelQueryBuilder = ModelQueryBuilder::forClass($TestModel::class);

        $this->assertEquals('SELECT `Model`.* FROM `Model`', $iModelQueryBuilder->toSQL());
    }

    public function testShouldHaveKnexQueryBuilderMethods(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        // Initialize the builder.
        $queryBuilder = ModelQueryBuilder::forClass($TestModel::class);

        // Call the methods.
        $queryBuilder
            ->select('name', 'id', 'age')
            ->join('AnotherTable', 'AnotherTable.modelId', 'Model.id')
            ->where('id', 10)
            ->where('height', '>', 180)
            ->where(['name' => 'test'])
        ->orWhere(function(ModelQueryBuilderBase $queryBuilder)
            {
                // The builder passed to these functions should be a QueryBuilderBase instead of
                // a raw query builder.
                $this->assertInstanceOf(ModelQueryBuilder::class, $queryBuilder);
                $queryBuilder->where('age', '<', 10)->andWhere('eyeColor', 'blue');
            });

        // Assert.
        $this->assertEquals(
            implode(' ',
            [
                'SELECT `name`, `id`, `age` FROM `Model`',
                'INNER JOIN `AnotherTable` ON(`AnotherTable`.`modelId` = `Model`.`id`)',
                'WHERE `id` = ?',
                'AND `height` > ?',
                'AND `name` = ?',
                'OR (`age` < ? AND `eyeColor` = ?)',
            ]),
            $queryBuilder->toSQL()
        );
    }

    public function testShouldReturnAQueryBuilderFromTimeoutMethod(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class)->timeout(3000);

        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
    }

    // #################################################################
    // ############################# WHERE #############################
    // #################################################################

    public function testShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues1(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->where('SomeTable.someColumn', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `SomeTable`.`someColumn` = `SomeOtherTable`.`someOtherColumn`',
            $query
        );
    }

    public function testShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues2(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->where('SomeTable.someColumn', '>', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `SomeTable`.`someColumn` > `SomeOtherTable`.`someOtherColumn`',
            $query
        );
    }

    public function testShouldFailWithInvalidOperatorUsingRef(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator "lol" is not permitted');

        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        ModelQueryBuilder::forClass($TestModel::class)
            ->where('SomeTable.someColumn', 'lol', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();
    }

    public function testOrWhereShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->where('id', 10)
            ->orWhere('SomeTable.someColumn', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `id` = ? OR `SomeTable`.`someColumn` = `SomeOtherTable`.`someOtherColumn`',
            $query
        );
    }

    // #################################################################
    // ####################### WHERE COMPOSITE #########################
    // #################################################################

    public function testShouldCreateMultipleWhereQueries(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereComposite(['A.a', 'B.b'], '>', [1, 2])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a` > ? AND `B`.`b` > ?)',
            $query
        );
    }

    public function testShouldFailWithInvalidOperatorUsingWhereComposite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator "lol" is not permitted');

        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        ModelQueryBuilder::forClass($TestModel::class)
            ->whereComposite('SomeTable.someColumn', 'lol', 'SomeOtherTable.someOtherColumn')
            ->toSQL();
    }

    public function testOperatorShouldDefaultToEqualWhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereComposite(['A.a', 'B.b'], [1, 2])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a` = ? AND `B`.`b` = ?)',
            $query
        );
    }

    public function testShouldWorkLikeANormalWhereWhenOneColumnIsGiven1WhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereComposite(['A.a'], 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` = ?',
            $query
        );
    }

    public function testShouldWorkLikeANormalWhereWhenOneColumnIsGiven2WhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereComposite('A.a', 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` = ?',
            $query
        );
    }

    // #################################################################
    // ##################### WHERE COMPOSITE IN ########################
    // #################################################################

    public function testShouldCreateWhereInQueryForCompositeIdAndSingleChoice(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?))',
            $query
        );
    }

    public function testShouldCreateWhereInQueryForCompositeIdAndArrayOfChoices(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2], [3, 4]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?), (?, ?))',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven1(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite(['A.a'], [[1], [3]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven2(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite('A.a', [[1], [3]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven3(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite('A.a', [1, 3])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven4(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $subQuery = ModelQueryBuilder::forClass($TestModel::class)
            ->select('a');

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite('A.a', $subQuery)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN('.$subQuery->toSQL().')',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven5(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite('A.a', 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?)',
            $query
        );
    }

    public function testShouldCreateWhereInQueryForCompositeIdAndASubquery(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $subQuery = ModelQueryBuilder::forClass($TestModel::class)
            ->select('a', 'b');

        $query = ModelQueryBuilder::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], $subQuery)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`,`B`.`b`) IN('.$subQuery->toSQL().')',
            $query
        );
    }

    public function testShouldConvertArrayQueryResultIntoModelInstances(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [['a' => 1], ['a' => 2]];

        $results = ModelQueryBuilder::forClass($TestModel::class)->run();

        $this->assertCount(2, $results);
        $this->assertInstanceOf($TestModel::class, $results[0]);
        $this->assertInstanceOf($TestModel::class, $results[1]);
        // $this->assertEquals($this->mockQueryResults, $results); // This assertion makes no sense. The results should be of type Model, not array.
    }

    public function testShouldConvertAnObjectQueryResultIntoAModelInstance(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [['a' => 1]];

        $result = ModelQueryBuilder::forClass($TestModel::class)
            ->first()
            ->run();

        $this->assertInstanceOf($TestModel::class, $result);
        $this->assertEquals(1, $result->a);
    }

    public function testShouldPassTheQueryBuilderAsThisAndParameterForTheHooks(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;
            public int $b;
            public int $c;
            public int $d;
            public int $e;
            public int $f;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [['a' => 1]];

        $text = '';

        $exception = new \Exception('abort');

        try
        {
            ModelQueryBuilder::forClass($TestModel::class)
                ->runBefore(function(ModelQueryBuilder $builder) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $text .= 'a';
                    })
                ->onBuild(function(ModelQueryBuilder $builder) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $text .= 'b';
                    })
                ->onBuildQueryBuilder(function(ModelQueryBuilder $iBuilder, QueryBuilder $iQueryBuilder) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $iBuilder);
                        // Assuming isQueryBuilder() is equivalent to checking if $iQueryBuilder is an instance of a specific class
                        $this->assertInstanceOf(QueryBuilder::class, $iQueryBuilder);
                        $text .= 'c';
                    })
                ->runAfter(function($builder, ?array $data) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $text .= 'd';
                    })
                ->runAfter(function($builder, ?array $data) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $text .= 'e';
                    })
                ->runAfter(function() use($exception)
                    {
                        throw $exception;
                    })
                ->onError(function($builder, \Exception $err) use(&$text)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $this->assertEquals('abort', $err->getMessage());
                        $text .= 'f';
                    })
                ->run();
        }
        catch(\Exception $error)
        {
            if($error !== $exception) throw $error;

            // Catch the exception so the test can continue and we can check the $text value
        }

        $this->assertEquals('abcdef', $text);
    }

    public function testThrowingAtAnyPhaseShouldCallTheOnErrorHook(): void
    {
        $called = false;

        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        try
        {
            ModelQueryBuilder::forClass($TestModel::class)
                ->runBefore(function($result, $builder)
                    {
                        throw new \Exception();
                    })
                ->onError(function($builder, $err) use (&$called)
                    {
                        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
                        $called = true;
                    })
                ->run();
        }
        catch(\Exception $e)
        {
            // Handle error if needed
        }

        $this->assertTrue($called);
    }

    public function testAnyReturnValueFromOnErrorShouldBeTheResultOfTheQuery(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $result = null;

        try
        {
            $result = ModelQueryBuilder::forClass($TestModel::class)
                ->runBefore(function($builder, $result)
                    {
                        throw new \Exception();
                    })
                ->onError(function($builder, $err)
                    {
                        return 'my custom error';
                    })
                ->run();
        }
        catch(\Exception $e)
        {
            // Handle error if needed
        }

        $this->assertEquals('my custom error', $result);
    }

    public function testShouldCallRunMethodsInTheCorrectOrder(): void
    {
        $this->mockQueryResults = [['a'=>0]];

        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $res = 0;

        try
        {
            ModelQueryBuilder::forClass($TestModel::class)
                ->runBefore(function() use(&$res)
                    {
                        $this->assertEquals(0, $res);
                        ++$res;
                    })
                ->runBefore(function() use(&$res)
                    {
                        $this->assertEquals(1, $res);
                        ++$res;
                    })
                ->runBefore(function() use(&$res)
                    {
                        $this->assertEquals(2, $res);
                        ++$res;
                    })
                ->runAfter(function($builder) use(&$res)
                    {
                        $this->assertEquals(3, $res);
                        // Assuming there's a delay or wait function available in PHP
                        return ++$res;
                    })
                ->runAfter(function($builder) use(&$res)
                    {
                        $this->assertEquals(4, $res);
                        return ++$res;
                    })
                ->run();
        }
        catch(\Exception $e)
        {
            // Handle error if needed
        }

        $this->assertEquals(5, $res);
    }

    public function testShouldNotExecuteQueryIfAnErrorIsThrownFromRunBefore(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        try {
            ModelQueryBuilder::forClass($TestModel::class)
                ->runBefore(function()
                    {
                        throw new \Exception('some error');
                    })
                ->onBuild(function()
                    {
                        $this->fail('should not get here');
                    })
                ->runAfter(function()
                    {
                        $this->fail('should not get here');
                    })
                ->run();
        }
        catch(\Exception $e)
        {
            $this->assertEquals('some error', $e->getMessage());
            // Replace 'executedQueries' with the appropriate method to get the number of executed queries
            $this->assertCount(0, $this->executedQueries);
        }
    }

    public function testShouldCallCustomFindImplementationDefinedByFindOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    $this->assertEquals($iBuilder, $this);
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('select "Model".* from "Model" where "a" = 1', $this->executedQueries[0]);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfInsertIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->insert(['a' => 1])
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('insert into "Model" ("a") values (1) returning "id"', $this->executedQueries[0]);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfUpdateIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->update(['a' => 1])
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('update "Model" set "a" = 1', $this->executedQueries[0]);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfDeleteIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Objection\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelQueryBuilder::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->delete()
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model`', $this->executedQueries[0]);
    }
}
