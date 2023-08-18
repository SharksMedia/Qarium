<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Exceptions\ModifierNotFoundError;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQBase;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\Qarium;
use Sharksmedia\Qarium\Operations\DeleteOperation;
use Sharksmedia\Qarium\Operations\FindOperation;
use Sharksmedia\Qarium\Operations\InsertOperation;
use Sharksmedia\Qarium\Operations\RelateOperation;
use Sharksmedia\Qarium\Operations\UnrelateOperation;
use Sharksmedia\Qarium\Operations\UpdateOperation;
use Sharksmedia\Qarium\ReferenceBuilder;
use Sharksmedia\SharQ\Client;
use Sharksmedia\SharQ\Client\MySQL;
use Sharksmedia\SharQ\Config;
use Sharksmedia\SharQ\Query;
use Sharksmedia\SharQ\SharQ;
use Tests\Support\MockMySQLClient;
use Tests\Support\MockPDOStatement;

class SharQTest extends Unit
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

        $iClient = new MockMySQLClient($iConfig, function(Query $iQuery, array $options)
        {
            $sql = $iQuery->getSQL();
            $bindings = $iQuery->getBindings();

            $iPDOStatement = new MockPDOStatement();

            $iPDOStatement->setResults(array_shift($this->mockQueryResults) ?? []);

            $this->executedQueries[] =
            [
                'sql'=>$sql,
                'bindings'=>$bindings
            ];

            return $iPDOStatement;
        });

        $_GLOBAL['MockMySQLClient'] = $iClient;

        //
        // $iClient = Client::create($iConfig);

        $iClient->initializeDriver();

        Qarium::setClient($iClient);

        $iSharQ = new SharQ($iClient, 'db');

        Model::setSharQ($iSharQ);
        // You may initialize objects here if necessary

        $this->mockQueryResults = [];
        $this->executedQueries = [];

        $iModelReflectionClass = new \ReflectionClass(Model::class);
        $iModelReflectionClass->setStaticPropertyValue('metadataCache', []);

        $iModelReflectionClass = new \ReflectionClass(Model::class);
        $iModelReflectionClass->setStaticPropertyValue('iRelationCache', []);

        parent::_before();
    }

    protected function _after()
    {
        $this->mockQueryResults = [];
        $this->executedQueries = [];
        
        parent::_after();
    }

    // protected function setUp(): void
    // {
    //     $this->mockQueryResults = [];
    //     $this->executedQueries = [];
    // }

    public static function ref(string $expression): ReferenceBuilder
    {
        return new ReferenceBuilder($expression);
    }

    public static function createFindOperation(ModelSharQ $iBuilder, array $whereObj): FindOperation
    {
        $TestFindOperation = new class('find') extends FindOperation
        {
            public ?array $whereObject = null;

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool { return true; }

            public function onAfter2(ModelSharQOperationSupport $builder, &$result) { return $result; }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                return $iSharQ->where($this->whereObject);
            }
        };

        $iFindOperation = new $TestFindOperation('find');

        $iFindOperation->whereObject = $whereObj;

        return $iFindOperation;
    }

    public static function createInsertOperation(ModelSharQ $iBuilder, array $whereObj): InsertOperation
    {
        $TestInsertOperation = new class('insert') extends InsertOperation
        {
            public array $insertData = [];

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool { return true; }

            public function onBefore3(ModelSharQOperationSupport $builder, ...$arguments): bool { return true; }

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result) { return $result; }

            public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
            {
                $this->iModels = [$arguments[0]];

                return true;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                $modelClass = $iBuilder->getModelClass();

                $iModel = $modelClass::ensureModel($this->iModels[0], $this->modelOptions);

                $this->iModels = [$iModel];
                
                $data = $this->iModels[0]->toDatabaseArray($iBuilder);

                $data = array_merge($data, $this->insertData);

                $modelClass = $iBuilder->getModelClass();
                
                $this->iModels[0] = $data;

                return $iSharQ->insert($this->iModels);
            }
        };

        $iInsertOperation = new $TestInsertOperation('insert');

        $iInsertOperation->insertData = $whereObj;

        return $iInsertOperation;
    }

    public static function createUpdateOperation(ModelSharQ $iBuilder, array $whereObj): UpdateOperation
    {
        $TestUpdateOperation = new class('update') extends UpdateOperation
        {
            public $testUpdateData = [];

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool { return true; }

            public function onBefore3(ModelSharQOperationSupport $builder, ...$arguments): bool { return true; }

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result) { return $result; }

            public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
            {
                $data = $arguments[0];

                $modelClass = $iBuilder->getModelClass();

                $this->iModel = $modelClass::ensureModel($data, $this->modelOptions);

                return true;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                $data = $this->iModel->toDatabaseArray($iBuilder);

                $data = array_merge($data, $this->testUpdateData);

                $modelClass = $iBuilder->getModelClass();
                
                $this->iModel = $modelClass::ensureModel($data, $this->modelOptions);

                return $iSharQ->update($this->iModel);
            }
        };

        $iUpdateOperation = new $TestUpdateOperation('update');

        $iUpdateOperation->testUpdateData = $whereObj;

        return $iUpdateOperation;
    }

    public static function createDeleteOperation(ModelSharQ $iBuilder, array $whereObj): DeleteOperation
    {
        $TestDeleteOperation = new class('delete') extends DeleteOperation
        {
            public array $whereObject = [];

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result) { return $result; }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                return $iSharQ->delete()->where($this->whereObject);
            }
        };

        $iDeleteOperation = new $TestDeleteOperation('delete');

        $iDeleteOperation->whereObject = $whereObj;

        return $iDeleteOperation;
    }

    // Tests
    public function testShouldHaveSharQMethods(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };
        
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
            'queryBuilder', // this method is added to the knex mock, but should not available on objection's SharQ
            'raw', // this method is added to the knex mock, but should not available on objection's SharQ

            'getClient',
            'getSchema',
            'getSelectMethod',
            'getMethod',
            'getSingle',
            'getStatements',
            'hasAlias',
            'fetchMode',
            '__clone',
            '_where',
            '_join',
            '_whereWrapped',

        ];

        $builder = ModelSharQ::forClass($TestModel::class);

        // $missingFunctions = [];

        $queryBuilderMethods = get_class_methods(SharQ::class);
        foreach($queryBuilderMethods as $qbMethodName)
        {
            if(!in_array($qbMethodName, $ignoreMethods, true))
            {
                // if(!method_exists($builder, $qbMethodName)) $missingFunctions[] = $qbMethodName;

                $this->assertTrue(method_exists($builder, $qbMethodName), "SharQ method '" . $qbMethodName . "' is missing from ModelSharQ");
            }
        }
    }

    public function testModelClassShouldReturnTheModelClass(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };

        $this->assertEquals($TestModel::class, ModelSharQ::forClass($TestModel::class)->getModelClass());
    }

    public function testModifyShouldExecuteTheGivenFunctionAndPassTheBuilderToIt(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };

        $builder = ModelSharQ::forClass($TestModel::class);
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };

        $builder = ModelSharQ::forClass($TestModel::class);
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model
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

        $builder = ModelSharQ::forClass($TestModel::class);

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
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };

        $builder = ModelSharQ::forClass($TestModel::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "unknown".');

        $builder->modify('unknown');
    }

    public function testModifyShouldDoNothingWhenReceivingUndefined(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model { };

        $builder = ModelSharQ::forClass($TestModel::class);
        $res = null;

        $res = $builder->modify(null);

        $this->assertSame($builder, $res);
    }

    public function testModifyAcceptAListOfStringsAndCallTheCorrespondingModifiers(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
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

        $builder = ModelSharQ::forClass($TestModel::class);
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
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

        $builder = ModelSharQ::forClass($TestModel::class);

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
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function modifierNotFound(ModelSharQ $iBuilder, string $modifierName): void
            {
                parent::modifierNotFound($iBuilder, $modifierName);
            }
        };

        $builder = ModelSharQ::forClass($TestModel::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "unknown".');

        $builder->modify('unknown');
    }

    // public function testShouldNotThrowIfModifierNotFoundHandlesAnUnknownModifier(): void
    // {
    //     $caughtModifier = null;
    //     $TestModel = new class extends \Sharksmedia\Qarium\Model 
    //     {
    //         public static function modifierNotFound($builder, $modifier) use(&$caughtModifier) 
    //         {
    //             $caughtModifier = $modifier;
    //         }
    //     };
    //
    //     $builder = ModelSharQ::forClass($TestModel::class);
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $iModelSharQ = ModelSharQ::forClass($TestModel::class);

        $this->assertEquals('SELECT `Model`.* FROM `Model`', $iModelSharQ->toSQL());
    }

    public function testShouldHaveSharQMethods2(): void
    {
        // Doesn't test all the methods. Just enough to make sure the method calls are correctly
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        // Initialize the builder.
        $queryBuilder = ModelSharQ::forClass($TestModel::class);

        // Call the methods.
        $queryBuilder
            ->select('name', 'id', 'age')
            ->join('AnotherTable', 'AnotherTable.modelId', 'Model.id')
            ->where('id', 10)
            ->where('height', '>', 180)
            ->where(['name' => 'test'])
        ->orWhere(function(ModelSharQBase $queryBuilder)
            {
                // The builder passed to these functions should be a SharQBase instead of
                // a raw query builder.
                $this->assertInstanceOf(ModelSharQ::class, $queryBuilder);
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

    public function testShouldReturnASharQFromTimeoutMethod(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $builder = ModelSharQ::forClass($TestModel::class)->timeout(3000);

        $this->assertInstanceOf(ModelSharQ::class, $builder);
    }

    // #################################################################
    // ############################# WHERE #############################
    // #################################################################

    public function testShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues1(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->where('SomeTable.someColumn', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `SomeTable`.`someColumn` = `SomeOtherTable`.`someOtherColumn`',
            $query
        );
    }

    public function testShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues2(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
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

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        ModelSharQ::forClass($TestModel::class)
            ->where('SomeTable.someColumn', 'lol', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();
    }

    public function testOrWhereShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
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

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        ModelSharQ::forClass($TestModel::class)
            ->whereComposite('SomeTable.someColumn', 'lol', 'SomeOtherTable.someOtherColumn')
            ->toSQL();
    }

    public function testOperatorShouldDefaultToEqualWhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereComposite(['A.a', 'B.b'], [1, 2])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a` = ? AND `B`.`b` = ?)',
            $query
        );
    }

    public function testShouldWorkLikeANormalWhereWhenOneColumnIsGiven1WhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereComposite(['A.a'], 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` = ?',
            $query
        );
    }

    public function testShouldWorkLikeANormalWhereWhenOneColumnIsGiven2WhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
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
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?))',
            $query
        );
    }

    public function testShouldCreateWhereInQueryForCompositeIdAndArrayOfChoices(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2], [3, 4]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?), (?, ?))',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven1(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a'], [[1], [3]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven2(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite('A.a', [[1], [3]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven3(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite('A.a', [1, 3])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?, ?)',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven4(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $subQuery = ModelSharQ::forClass($TestModel::class)
            ->select('a');

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite('A.a', $subQuery)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN('.$subQuery->toSQL().')',
            $query
        );
    }

    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven5(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite('A.a', 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?)',
            $query
        );
    }

    public function testShouldCreateWhereInQueryForCompositeIdAndASubquery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $subQuery = ModelSharQ::forClass($TestModel::class)
            ->select('a', 'b');

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], $subQuery)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`,`B`.`b`) IN('.$subQuery->toSQL().')',
            $query
        );
    }

    public function testShouldConvertArrayQueryResultIntoModelInstances(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['a' => 1], ['a' => 2]]];

        $results = ModelSharQ::forClass($TestModel::class)->run();

        $this->assertCount(2, $results);
        $this->assertInstanceOf($TestModel::class, $results[0]);
        $this->assertInstanceOf($TestModel::class, $results[1]);
        // $this->assertEquals($this->mockQueryResults, $results); // This assertion makes no sense. The results should be of type Model, not array.
    }

    public function testShouldConvertAnObjectQueryResultIntoAModelInstance(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['a' => 1]]];

        $result = ModelSharQ::forClass($TestModel::class)
            ->first()
            ->run();

        $this->assertInstanceOf($TestModel::class, $result);
        $this->assertEquals(1, $result->a);
    }

    public function testShouldPassTheSharQAsThisAndParameterForTheHooks(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;
            public int $b;
            public int $c;
            public int $d;
            public int $e;
            public int $f;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['a' => 1]]];

        $text = '';

        $exception = new \Exception('abort');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('abort');

        ModelSharQ::forClass($TestModel::class)
            ->runBefore(function(ModelSharQ $builder) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $text .= 'a';
                })
            ->onBuild(function(ModelSharQ $builder) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $text .= 'b';
                })
            ->onBuildSharQ(function($iBuilder, $iSharQ) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $iBuilder);
                    // Assuming isSharQ() is equivalent to checking if $iSharQ is an instance of a specific class
                    $this->assertInstanceOf(SharQ::class, $iSharQ);
                    $text .= 'c';
                })
            ->runAfter(function($builder, ?array $data) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $text .= 'd';
                })
            ->runAfter(function($builder, ?array $data) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $text .= 'e';
                })
            ->runAfter(function() use($exception)
                {
                    throw $exception;
                })
            ->onError(function($builder, \Exception $err) use(&$text)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $this->assertEquals('abort', $err->getMessage());
                    $text .= 'f';
                })
            ->run();

        $this->assertEquals('abcdef', $text);
    }

    public function testThrowingAtAnyPhaseShouldCallTheOnErrorHook(): void
    {
        $called = false;

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('run before error');

        ModelSharQ::forClass($TestModel::class)
            ->runBefore(function($result, $builder)
                {
                    throw new \Exception('run before error');
                })
            ->onError(function($builder, $err) use (&$called)
                {
                    $this->assertInstanceOf(ModelSharQ::class, $builder);
                    $called = true;
                })
            ->run();

        $this->assertTrue($called);
    }

    public function testAnyReturnValueFromOnErrorShouldBeTheResultOfTheQuery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $result = null;

        $result = ModelSharQ::forClass($TestModel::class)
            ->returnError(true)
            ->runBefore(function($builder, $result)
                {
                    throw new \Exception('run before error');
                })
            ->onError(function($builder, $err)
                {
                    return 'my custom error';
                })
            ->run();

        $this->assertEquals('my custom error', $result);
    }

    public function testShouldCallRunMethodsInTheCorrectOrder(): void
    {
        $this->mockQueryResults = [[['a'=>0]]];

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [
                        'Field'=>'a',
                    ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $res = 0;

        ModelSharQ::forClass($TestModel::class)
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

        $this->assertEquals(5, $res);
    }

    public function testShouldNotExecuteQueryIfAnErrorIsThrownFromRunBefore(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

        ModelSharQ::forClass($TestModel::class)
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

    public function testShouldCallCustomFindImplementationDefinedByFindOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [
                        'Field'=>'a',
                    ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $builder = ModelSharQ::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    $iFindOperation = self::createFindOperation($iBuilder, ['a' => 1]);

                    return $iFindOperation;
                })
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('SELECT `Model`.* FROM `Model` WHERE `a` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfInsertIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [
                        'Field'=>'a',
                    ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $builder = ModelSharQ::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->insert(['a' => 1])
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('INSERT INTO `Model` (`a`) VALUES (?)', $this->executedQueries[0]['sql']);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfUpdateIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [
                        'Field'=>'a',
                    ]
                ]
            ];

            public int $a;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $builder = ModelSharQ::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->update(['a' => 1])
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('UPDATE `Model` SET `a` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfDeleteIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [
                        'Field'=>'a',
                    ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $builder = ModelSharQ::forClass($TestModel::class)
            ->findOperationFactory(function($iBuilder)
                {
                    return self::createFindOperation($iBuilder, ['a' => 1]);
                })
            ->delete()
            ->run();

        // Replace 'executedQueries' with the appropriate method to get the executed queries
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model`', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomInsertImplementationDefinedByInsertOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->insertOperationFactory(function($iBuilder)
                {
                    return self::createInsertOperation($iBuilder, ['b'=>2]);
                })
            ->insert(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('INSERT INTO `Model` (`a`, `b`) VALUES (?, ?)', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomUpdateImplementationDefinedByUpdateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->updateOperationFactory(function ($iBuilder) {
                return self::createUpdateOperation($iBuilder, ['b'=>2]);
            })
            ->update(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomPatchImplementationDefinedByPatchOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->patchOperationFactory(function ($iBuilder) {
                return self::createUpdateOperation($iBuilder, ['b'=>2]);
            })
            ->patch(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomDeleteImplementationDefinedByDeleteOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->deleteOperationFactory(function($iBuilder)
                {
                    return self::createDeleteOperation($iBuilder, ['id'=>100]);
                })
            ->delete()
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model` WHERE `id` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomRelateImplementationDefinedByRelateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->relateOperationFactory(function($iBuilder)
                {
                    return self::createInsertOperation($iBuilder, ['b'=>2]);
                })
            ->relate(['a'=>1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('INSERT INTO `Model` (`a`, `b`) VALUES (?, ?)', $this->executedQueries[0]['sql']);
    }

    public function testShouldCallCustomUnrelateImplementationDefinedByUnrelateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ]
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->unrelateOperationFactory(function($iBuilder)
                {
                    return self::createDeleteOperation($iBuilder, ['id'=>100]);
                })
            ->unrelate()
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model` WHERE `id` = ?', $this->executedQueries[0]['sql']);
    }

    public function testShouldBeAbleToExecuteSameQueryMultipleTimes(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        /** @var ModelSharQ $query */
        $query = ModelSharQ::forClass($TestModel::class)
        ->updateOperationFactory(function($builder)
            {
                return self::createUpdateOperation($builder, ['b' => 2]);
            })
            ->where('test', '<', 100)
            ->update(['a' => 1]);

        $query->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals($query->toQuery()->getSQL(), $this->executedQueries[0]['sql']);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ? WHERE `test` < ?', $this->executedQueries[0]['sql']);
        $this->executedQueries = [];

        $query->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals($query->toQuery()->getSQL(), $this->executedQueries[0]['sql']);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ? WHERE `test` < ?', $this->executedQueries[0]['sql']);
    }

    public function testResultSizeShouldCreateAndExecuteAQueryThatReturnsTheSizeOfTheQuery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a=null;
            public ?int $b=null;
            public ?int $test=null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a', ],
                    [ 'Field'=>'b', ],
                    [ 'Field'=>'test' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['count' => '123']]];
        
        $result = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->limit(10)
            ->offset(100)
            ->resultSize();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals(123, $result);
        $this->assertEquals(
            'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`',
            $this->executedQueries[0]['sql']
        );
    }

    public function testShouldConsiderWithSchemaWhenLookingForColumnInfo(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?int $id=null;
            // protected ?string $count=null;
            
            public static $RelatedClass = null;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'id', ],
                    // [ 'Field'=>'count' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }

            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                $mappings =
                [
                    'iRelated'=>
                    [
                        'relation' => Model::BELONGS_TO_ONE_RELATION,
                        'modelClass' => static::$RelatedClass::class,
                        'join'=>
                        [
                            'from' => 'Model.id',
                            'to' => 'Related.id',
                        ],
                    ]
                ];

                return $mappings;
            }

            public static function fetchTableMetadata(?Client $iClient=null, ?string $schema=null): array
            {
                parent::fetchTableMetadata(); // Just to get the query executed

                return static::$metadataCache;
            }
        };

        $TestModelRelated = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?int $id=null;

            protected static array $metadataCache =
            [
                'Related'=>
                [
                    [ 'Field'=>'id' ],
                ]
            ];

            public static function getTableName(): string { return 'Related'; }

            public static function getTableIDs(): array { return ['id']; }

            public static function fetchTableMetadata(?Client $iClient=null, ?string $schema=null): array
            {
                parent::fetchTableMetadata(); // Just to get the query executed

                return static::$metadataCache;
            }
        };

        $TestModel::$RelatedClass = $TestModelRelated;

        $this->executedQueries = [];
        // $this->mockQueryResults = [[ 'count'=>'123' ]];
        
        ModelSharQ::forClass($TestModel::class)
            ->withSchema('someSchema')
            ->withGraphJoined('iRelated')
            ->run();

        $expectedQueries =
        [
            // "select * from information_schema.columns where table_name = 'Model' and table_catalog = current_database() and table_schema = 'someSchema'",
            // "select * from information_schema.columns where table_name = 'Related' and table_catalog = current_database() and table_schema = 'someSchema'",
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Related']],
            ['sql'=>'SELECT `Model`.`id` AS `id`, `iRelated`.`id` AS `iRelated:id` FROM `someSchema`.`Model` LEFT JOIN `someSchema`.`Related` AS `iRelated` ON(`iRelated`.`id` = `Model`.`id`)', 'bindings'=>[]],
        ];

        $this->assertCount(3, $this->executedQueries);
        $this->assertEquals($expectedQueries, $this->executedQueries);
    }

    public function testRangeShouldReturnARangeAndTheTotalCount()
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $a=null;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['a' => '1']], [['count' => '123']]];

        $res = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->range(100, 200)
            ->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals([
            ['sql'=>'SELECT `Model`.* FROM `Model` WHERE `test` = ? ORDER BY `order` ASC LIMIT ? OFFSET ?', 'bindings'=>[100, 101, 100]],
            ['sql'=>'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`', 'bindings'=>[100]],
        ], $this->executedQueries);

        $iResultTestModel = new $TestModel();
        $iResultTestModel->a = '1';

        $this->assertEquals(123, $res['total']);
        $this->assertEquals([$iResultTestModel], $res['results']);
    }

    public function testPageShouldReturnAPageAndTheTotalCount()
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $a=null;

            public static function getTableName(): string { return 'Model'; }
        };

        $this->mockQueryResults = [[['a' => '2']], [['count' => '123']]];

        $res = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->page(10, 100)
            ->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals([
            ['sql'=>'SELECT `Model`.* FROM `Model` WHERE `test` = ? ORDER BY `order` ASC LIMIT ? OFFSET ?', 'bindings'=>[100, 100, 1000]],
            ['sql'=>'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`', 'bindings'=>[100]],
        ], $this->executedQueries);

        $iResultTestModel = new $TestModel();
        $iResultTestModel->a = '2';

        $this->assertEquals(123, $res['total']);
        $this->assertEquals([$iResultTestModel], $res['results']);
    }

    public function testOperationTypeMethodsShouldReturnTrueOnlyForTheRightOperations()
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $id=null;

            public static $RelatedTestModelClass=null;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'someRel' => [
                        'relation'=>Model::HAS_MANY_RELATION,
                        'modelClass'=>static::$RelatedTestModelClass,
                        'join' => [
                            'from'=>'Model.id',
                            'to'=>'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId=null;

            public static function getTableName(): string { return 'ModelRelation'; }
            public static function getTableIDs(): array { return ['someRelId']; }
        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;

        $queries = [
            'find' => $TestModel::query(),
            'insert' => $TestModel::query()->insert([]),
            'update' => $TestModel::query()->update([]),
            'patch' => $TestModel::query()->patch([]),
            'delete' => $TestModel::query()->delete(),
            'relate' => $TestModel::relatedQuery('someRel')->relate(1),
            'unrelate' => $TestModel::relatedQuery('someRel')->unrelate(),
        ];

        $getMethodName = function ($name) {
            return 'is' . ucfirst($name === 'patch' ? 'update' : $name);
        };

        foreach ($queries as $name => $query) {
            foreach ($queries as $other => $_) {
                $method = $getMethodName($other);
                $this->assertEquals($method === $getMethodName($name), $query->$method(), "queries['$name']->$method()");
                $this->assertEquals(str_contains($name, 'relate'), $query->hasWheres(), "queries['$name']->hasWheres()");
                $this->assertFalse($query->hasSelects(), "queries['$name']->hasSelects()");
            }

        }
    }

    public function testHasWheresShouldReturnTrueForAllVariantsOfWhereQueries(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $id=null;
            public ?int $someId=null;

            public static $RelatedTestModelClass=null;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'manyToManyRelation' => [
                        'relation'=>Model::MANY_TO_MANY_RELATION,
                        'modelClass'=>static::$RelatedTestModelClass,
                        'join' => [
                            'from'=>'Model.id',
                            'through'=> [
                                'from'=>'ModelRelation.someRelId',
                                'to'=>'ModelRelation.someRelId',
                            ],
                            'to'=>'ModelRelation.someRelId',
                        ],
                    ],
                    'hasManyRelation' => [
                        'relation'=>Model::HAS_MANY_RELATION,
                        'modelClass'=>static::$RelatedTestModelClass,
                        'join' => [
                            'from'=>'Model.id',
                            'to'=>'ModelRelation.someRelId',
                        ],
                    ],
                    'belongsToOneRelation'=> [
                        'relation'=>Model::BELONGS_TO_ONE_RELATION,
                        'modelClass'=>static::$RelatedTestModelClass,
                        'join' => [
                            'from'=>'Model.id',
                            'to'=>'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId=null;

            public static function getTableName(): string { return 'ModelRelation'; }
            public static function getTableIDs(): array { return ['someRelId']; }

        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;

        $this->assertFalse($TestModel::query()->hasWheres());
        $this->assertFalse($TestModel::query()->insert([])->hasWheres());
        $this->assertFalse($TestModel::query()->update([])->hasWheres());
        $this->assertFalse($TestModel::query()->patch([])->hasWheres());
        $this->assertFalse($TestModel::query()->delete()->hasWheres());

        $wheres = [
            'findOne', 'findById', 'where', 'andWhere', 'orWhere',
            'whereNot', 'orWhereNot', 'whereRaw', 'andWhereRaw', 'orWhereRaw',
            'whereWrapped', 'whereExists', 'orWhereExists', 'whereNotExists', 
            'orWhereNotExists', 'whereIn', 'orWhereIn', 'whereNotIn', 'orWhereNotIn',
            'whereNull', 'orWhereNull', 'whereNotNull', 'orWhereNotNull', 'whereBetween',
            'andWhereBetween', 'whereNotBetween', 'andWhereNotBetween', 'orWhereBetween',
            'orWhereNotBetween', 'whereColumn', 'andWhereColumn', 'orWhereColumn',
            'whereNotColumn', 'andWhereNotColumn', 'orWhereNotColumn'
        ];

        foreach($wheres as $name)
        {
            $query = $TestModel::query()->$name(1, '=', 1);
            $this->assertTrue($query->hasWheres(), "TestModel::query()->$name()->hasWheres()");
        }

        $model = $TestModel::createFromDatabaseArray(['id' => 1, 'someId' => 1]);

        $query = $model->lquery();
        $this->assertTrue($query->hasWheres());

        $query = $model->lquery()->withGraphJoined('manyToManyRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('belongsToOneRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('hasManyRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('manyToManyRelation');
        $this->assertTrue($query->hasWheres());
    }

    public function testHasSelectsShouldReturnTrueForAllVariantsOfSelectQueries(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $selects = [
            'select', 'columns', 'column', 'distinct', 'count',
            'countDistinct', 'min', 'max', 'sum', 'sumDistinct',
            'avg', 'avgDistinct'
        ];

        foreach ($selects as $name) {
            $query = $TestModel::query()->$name('arg');
            $this->assertTrue($query->hasSelects(), "TestModel::query()->$name('arg')->hasSelects()");
        }
    }

    public function testHasWithGraphShouldReturnTrueForQueriesWithEagerStatements(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $id=null;

            public static $RelatedTestModelClass=null;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'someRel' => [
                        'relation'=>Model::HAS_MANY_RELATION,
                        'modelClass'=>static::$RelatedTestModelClass,
                        'join' => [
                            'from'=>'Model.id',
                            'to'=>'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId=null;

            public static function getTableName(): string { return 'ModelRelation'; }
            public static function getTableIDs(): array { return ['someRelId']; }
        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;
        $query = $TestModel::query();
        $this->assertFalse($query->hasWithGraph());
        $query->withGraphJoined('someRel');
        $this->assertTrue($query->hasWithGraph());
        $query->clearWithGraph();
        $this->assertFalse($query->hasWithGraph());
    }

    public function testHasShouldMatchDefinedQueryOperations(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $operations = [
            'range', 'orderBy', 'limit', 'where', 'andWhere', 'whereRaw',
            'havingWrapped', 'rightOuterJoin', 'crossJoin', 'offset',
            'union', 'count', 'avg', 'with'
        ];

        foreach ($operations as $operation) {
            $query = $TestModel::query()->$operation('arg');
            foreach ($operations as $testOperation) {
                $this->assertEquals($testOperation === $operation, $query->has($testOperation), "TestModel::query()->$operation('arg')->has('$testOperation')");
                $this->assertEquals($testOperation === $operation, $query->has(preg_quote($testOperation, '/')), "TestModel::query()->$operation('arg')->has('/^$testOperation$/')");
            }
        }
    }

    public function testClearShouldRemoveMatchingQueryOperations(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $operations = ['where', 'limit', 'offset', 'count'];

        foreach($operations as $operation)
        {
            $query = $TestModel::query();
            foreach($operations as $operationToApply)
            {
                $query->$operationToApply('arg');
            }

            $this->assertTrue($query->has($operation), "query()->has('$operation')");
            $this->assertFalse($query->clear($operation)->has($operation), "query()->clear('$operation')->has('$operation')");
            foreach($operations as $testOperation) {
                $this->assertEquals($testOperation !== $operation, $query->has($testOperation), "query()->has('$testOperation')");
            }
        }
    }

    public function testUpdateShouldCallBeforeUpdateOnTheModel(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public function lbeforeUpdate($context): void 
            {
                $this->c = 'beforeUpdate';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);

        $TestModel::query()
            ->update($model)
            ->run();

        $this->assertEquals('beforeUpdate', $model->c);

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals(
            [
                // ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
                ['sql'=>'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings'=>[10, 'test', 'beforeUpdate']],
            ],
            $this->executedQueries
        );
    }

    public function testUpdateShouldCallBeforeUpdateOnTheModelAsync(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeUpdate($context): void 
            {
                $this->c = 'beforeUpdate';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);
        $TestModel::query()
            ->update($model)
            ->run();

        $this->assertEquals('beforeUpdate', $model->c);
        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals([
            // ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
            ['sql'=>'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings'=>['10', 'test', 'beforeUpdate']],
        ], $this->executedQueries);
    }

    public function testPatchShouldCallBeforeUpdateOnTheModel(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeUpdate($context): void 
            {
                $this->c = 'beforeUpdate';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);
        $TestModel::query()->patch($model)->run();

        $this->assertEquals('beforeUpdate', $model->c);
        // Assuming lastQuery() gives us the latest query, if not you may need another mechanism.
        $this->assertEquals([
            ['sql'=>'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings'=>[10, 'test', 'beforeUpdate']],
            ], $this->executedQueries);
    }

    public function testPatchShouldCallBeforeUpdateOnTheModelAsync(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeUpdate($context): void 
            {
                usleep(5000); // Simulate a delay of 5 milliseconds
                $this->c = 'beforeUpdate';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);
        $TestModel::query()->patch($model)->run();

        $this->assertEquals('beforeUpdate', $model->c);
        $this->assertEquals([
            ['sql'=>'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings'=>[10, 'test', 'beforeUpdate']],
        ], $this->executedQueries);
    }

    public function testInsertShouldCallBeforeInsertOnTheModel(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeInsert($context): void 
            {
                $this->c = 'beforeInsert';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);
        $TestModel::query()->insert($model)->run();

        $this->assertEquals('beforeInsert', $model->c);
        $this->assertEquals([
            ['sql'=>'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, ?, ?)', 'bindings'=>[10, 'test', 'beforeInsert']],
        ], $this->executedQueries);
    }

    public function testInsertShouldCallBeforeInsertOnTheModelAsync(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model'=>
                [
                    [ 'Field'=>'a' ],
                    [ 'Field'=>'b' ],
                    [ 'Field'=>'c' ],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeInsert($context): void 
            {
                usleep(5000); // Simulate a delay of 5 milliseconds
                $this->c = 'beforeInsert';
            }

            public function lafterFind($arguments): void 
            {
                throw new \Exception('$afterFind should not be called');
            }
        };

        $model = $TestModel::createFromDatabaseArray(['a' => 10, 'b' => 'test']);
        $TestModel::query()->insert($model)->run();

        $this->assertEquals('beforeInsert', $model->c);
        $this->assertEquals([
            ['sql'=>'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, ?, ?)', 'bindings'=>[10, 'test', 'beforeInsert']],
        ], $this->executedQueries);
    }

    public function testShouldCallAfterFindOnModelIfNoWriteOperationSpecified(): void
    {
        // Mock database results
        $this->mockQueryResults =
        [[
            ['a' => 1],
            ['a' => 2],
        ]];

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lafterFind($context): void 
            {
                $this->b = $this->a * 2 + $context->x;
            }
        };

        $models = $TestModel::query()->context(['x' => 10])->run();

        $this->assertInstanceOf($TestModel::class, $models[0]);
        $this->assertInstanceOf($TestModel::class, $models[1]);
        $this->assertEquals(12, $models[0]->b);
        $this->assertEquals(14, $models[1]->b);
    }

    public function testShouldCallAfterFindOnModelIfNoWriteOperationSpecifiedAsync(): void
    {
        // Mock database results
        $this->mockQueryResults =
        [[
            ['a' => 1],
            ['a' => 2],
        ]];

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lafterFind($context): void 
            {
                // usleep(10000); // Simulate a delay of 10 milliseconds
                $this->b = $this->a * 2 + $context->x;
            }
        };

        $models = $TestModel::query()->context(['x' => 10])->run();

        $this->assertInstanceOf($TestModel::class, $models[0]);
        $this->assertInstanceOf($TestModel::class, $models[1]);
        $this->assertEquals(12, $models[0]->b);
        $this->assertEquals(14, $models[1]->b);
    }

    public function testShouldCallAfterFindBeforeAnyRunAfterHooks(): void
    {
        // Mock database results
        $this->mockQueryResults =
        [[
            ['a' => 1],
            ['a' => 2],
        ]];

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lafterFind($context): void 
            {
                $this->b = $this->a * 2 + $context->x;
            }
        };

        $models = $TestModel::query()
            ->context(['x' => 10])
            ->runAfter(function($iBuilder, $result)
                {
                    $iBuilder->context(['x' => 666]);
                    return $result;
                })
            ->run();

        $this->assertInstanceOf($TestModel::class, $models[0]);
        $this->assertInstanceOf($TestModel::class, $models[1]);
        $this->assertEquals(12, $models[0]->b);
        $this->assertEquals(14, $models[1]->b);
    }

    // public function testShouldNotBeAbleToCallSetQueryExecutorTwice(): void
    // {
    //     $this->expectException(\LogicException::class); // You can adjust the exception type to whatever you expect.
    //
    //     /** @var \Model $TestModel */
    //     $TestModel = new class extends \Sharksmedia\Qarium\Model
    //     {
    //         public static function getTableName(): string { return 'Model'; }
    //         public static function getTableIDs(): array { return ['id']; }
    //     };
    //
    //     $SharQ = ModelSharQ::forClass($TestModel::class);
    //     $SharQ->setQueryExecutor(function() {});
    //     $SharQ->setQueryExecutor(function() {});
    // }

    public function testClearWithGraphShouldClearEverythingRelatedToEager(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $SharQ = ModelSharQ::forClass($TestModel::class)
            ->withGraphJoined('a(f).b', ['f' => function(){}])
            ->modifyGraph('a', function(){});

        $this->assertNotNull($SharQ->findOperation('eager'));
        $SharQ->clearWithGraph();
        $this->assertNull($SharQ->findOperation('eager'));
    }

    public function testClearRejectShouldClearRemoveExplicitRejection(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $SharQ = ModelSharQ::forClass($TestModel::class);
        $SharQ->reject('error');


        $this->assertEquals('error', $SharQ->getExplicitRejectValue());
        $SharQ->clearReject();
        $this->assertNull($SharQ->getExplicitRejectValue());
    }

    public function testJoinRelatedShouldAddJoinClauseToCorrectPlace(): void
    {
        $M1 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $m2Id;

            protected static array $metadataCache =
            [
                'M1' =>
                [
                    ['Field'=>'id'],
                    ['Field'=>'m2id']
                ],
                'M2' =>
                [
                    ['Field'=>'id'],
                    ['Field'=>'m1Id']
                ],
                'Model' =>
                [
                    ['Field'=>'id'],
                ]
            ];
            
            public static function getTableName(): string { return 'M1'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $M2 = new class extends \Sharksmedia\Qarium\Model
        {
            public static $M1class;

            public $id;
            public $m1Id;

            protected static array $metadataCache =
            [
                'M1' =>
                [
                    ['Field'=>'id'],
                    ['Field'=>'m2Id']
                ],
                'M2' =>
                [
                    ['Field'=>'id'],
                    ['Field'=>'m1Id']
                ],
                'Model' =>
                [
                    ['Field'=>'id'],
                ]
            ];
            
            public static function getTableName(): string { return 'M2'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'm1' => [
                        'relation' => Model::HAS_MANY_RELATION,
                        'modelClass' => static::$M1class,
                        'join' => [
                            'from' => 'M2.id',
                            'to' => 'M1.m2Id',
                        ],
                    ],
                ];
            }
        };

        $M2::$M1class = $M1::class;

        $SharQ = $M2::query();
        $SharQ->joinRelated('m1', ['alias' => 'm'])
            ->join('M1', 'M1.id', 'M2.m1Id')
            ->run();

        $this->assertEquals(
            [
                ['sql' => 'SELECT `M2`.* FROM `M2` INNER JOIN `M1` AS `m` ON(`m`.`m2Id` = `M2`.`id`) INNER JOIN `M1` ON(`M1`.`id` = `M2`.`m1Id`)', 'bindings' => []]
            ],
            $this->executedQueries
        );
    }

    public function testAllSharQMethodsShouldWorkIfModelIsNotBoundToAKnexWhenTheQueryIs(): void
    {
        global $MockMySQLClient;

        /** @var \Model $UnboundModel */
        $UnboundModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public int $foo = 0;

            protected static array $metadataCache =
            [
                'Bar' =>
                [
                    ['Field'=>'id'],
                    ['Field'=>'foo']
                ],
            ];

            public static function getTableName(): string { return 'Bar'; }
            public static function getTableIDs(): array { return ['id']; }

            public function getUnsafeSharQ(): ?SharQ
            {
                return $this->context->iSharQ;
            }
        };

        $this->assertEquals(
            'UPDATE `Bar` SET `foo` = `foo` + ?',
            $UnboundModel::query($MockMySQLClient)->increment('foo', 10)->toString()
        );

        $this->assertEquals(
            'UPDATE `Bar` SET `foo` = `foo` - ?',
            $UnboundModel::query($MockMySQLClient)->decrement('foo', 5)->toString()
        );
    }

    public function testFirstShouldNotAddLimit1ByDefault(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $TestModel::query()->first()->run();
        $this->assertEquals(['sql' => 'SELECT `Model`.* FROM `Model`', 'bindings' => []], $this->executedQueries[0]);
    }

    public function testFirstShouldAddLimit1IfModelUseLimitInFirstIsTrue(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public const USE_LIMIT_IN_FIRST = true;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $TestModel::query()->first()->run();
        $this->assertEquals(['sql' => 'SELECT `Model`.* FROM `Model` LIMIT ?', 'bindings' => [1]], $this->executedQueries[0]);
    }

    public function testTableNameForShouldReturnTheTableName(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $query = $TestModel::query();
        $this->assertEquals('Model', $query->getTableNameFor($TestModel::class));
    }

    public function testTableNameForShouldReturnTheTableNameGivenInFrom(): void
    {
        $this->markTestSkipped('Not implemented yet');
        return;
        
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $query = $TestModel::query()->from('Lol');
        $this->assertEquals('Lol', $query->getTableNameFor($TestModel::class));
    }

    public function testTableRefForShouldReturnTheTableNameByDefault(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $query = $TestModel::query();
        $this->assertEquals('Model', $query->getTableRefFor($TestModel::class));
    }

    public function testTableRefForShouldReturnTheAlias(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $query = $TestModel::query()->alias('Lyl');
        $this->assertEquals('Lyl', $query->getTableRefFor($TestModel::class));
    }

    public function testShouldUseModelSharQInBuilderMethods(): void
    {
        $this->markTestSkipped('Not implemented yet');
        return;

        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static $ModelSharQ;
            public static $CustomSharQClass;

            public static function query($iTransactionOrClient=null): ModelSharQ
            {
                if(static::$ModelSharQ === null)
                {
                    static::$ModelSharQ = new static::$CustomSharQClass(static::class);
                }

                $query = static::$ModelSharQ::forClass(static::class)
                    ->transacting($iTransactionOrClient);

                static::onCreateQuery($query);

                return $query;
            }
        };

        $CustomSharQ = new class($TestModel::class) extends ModelSharQ {};

        $TestModel::$CustomSharQClass = $CustomSharQ::class;

        $checks = [];

        $TestModel::query()
        ->select('*', function($builder) use(&$checks, $CustomSharQ)
            {
                $checks[] = $builder instanceof $CustomSharQ;
            })
        ->where(function($builder) use(&$checks, $CustomSharQ)
            {
                $checks[] = $builder instanceof $CustomSharQ;

                $builder->where(function($builder) use(&$checks, $CustomSharQ)
                {
                    $checks[] = $builder instanceof $CustomSharQ;
                });
            })
        ->modify(function($builder) use(&$checks, $CustomSharQ)
            {
                $checks[] = $builder instanceof $CustomSharQ;
            })
            ->run();
    
        $this->assertCount(4, $checks);
        $this->assertTrue(array_reduce($checks, function($carry, $item)
        {
            return $carry && $item;
        }, true));
    }

    public function testHasSelectionAs(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->assertTrue($TestModel::query()->hasSelectionAs('foo', 'foo'));
        $this->assertFalse($TestModel::query()->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('foo as bar')->hasSelectionAs('foo', 'bar'));
        
        $this->assertFalse($TestModel::query()->select('foo')->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('*')->hasSelectionAs('foo', 'foo'));
        
        $this->assertFalse($TestModel::query()->select('*')->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('foo.*')->hasSelectionAs('foo.anything', 'anything'));
        
        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelectionAs('foo.anything', 'somethingElse'));
        
        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelectionAs('bar.anything', 'anything'));
    }

    public function testHasSelection(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->assertTrue($TestModel::query()->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->hasSelection('DifferentTable.foo'));

        $this->assertTrue($TestModel::query()->select('*')->hasSelection('DifferentTable.anything'));

        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelection('bar.anything'));
        $this->assertTrue($TestModel::query()->select('foo.*')->hasSelection('foo.anything'));
        
        $this->assertTrue($TestModel::query()->select('foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->select('foo')->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->select('foo')->hasSelection('DifferentTable.foo'));
        $this->assertFalse($TestModel::query()->select('foo')->hasSelection('bar'));

        $this->assertTrue($TestModel::query()->select('Model.foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->select('Model.foo')->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->select('Model.foo')->hasSelection('NotTestModel.foo'));
        $this->assertFalse($TestModel::query()->select('Model.foo')->hasSelection('bar'));

        $this->assertTrue($TestModel::query()->alias('t')->select('foo')->hasSelection('t.foo'));
        $this->assertTrue($TestModel::query()->alias('t')->select('t.foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->alias('t')->select('t.foo')->hasSelection('t.foo'));
        $this->assertFalse($TestModel::query()->alias('t')->select('foo')->hasSelection('Model.foo'));
    }

    public function testParseRelationExpression(): void
    {
        $this->markTestSkipped('Not implemented yet');
        return;

        $parsed = ModelSharQ::parseRelationExpression('[foo, bar.baz]');

        $expected = [
            '$name' => null,
            '$relation' => null,
            '$modify' => [],
            '$recursive' => false,
            '$allRecursive' => false,
            '$childNames' => ['foo', 'bar'],
            'foo' => [
                '$name' => 'foo',
                '$relation' => 'foo',
                '$modify' => [],
                '$recursive' => false,
                '$allRecursive' => false,
                '$childNames' => [],
            ],
            'bar' => [
                '$name' => 'bar',
                '$relation' => 'bar',
                '$modify' => [],
                '$recursive' => false,
                '$allRecursive' => false,
                '$childNames' => ['baz'],
                'baz' => [
                    '$name' => 'baz',
                    '$relation' => 'baz',
                    '$modify' => [],
                    '$recursive' => false,
                    '$allRecursive' => false,
                    '$childNames' => [],
                ],
            ],
        ];
        
        $this->assertEquals($expected, $parsed);
    }

    public function testAllowGraphWithSingleFunctionJoined(): void
    {
        $this->markTestSkipped('Not implemented yet');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('a')->withGraphJoined('a(f1)', ['f1' => function () {}])->run();

        $this->assertCount(1, $this->executedQueries);
    }

    public function testWithGraphJoinedAllowGraphOrder(): void
    {
        $this->markTestSkipped('Not implemented yet');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->withGraphJoined('a(f1)', ['f1' => function () {}])->allowGraph('a')->run();

        $this->assertCount(1, $this->executedQueries);
    }

    public function testAllowGraphComplexWithGraphJoinedSimple(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static array $metadataCache =
            [
                'Model'=>[ ['Field'=>'a'] ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('a')->run();
    }

    public function testAllowGraphComplexWithGraphJoinedNested(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $b;
            public $c;

            public static array $metadataCache =
            [
                'Model'=>[
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'c'],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'b'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'c'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('b.c')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`c` AS `c`, `b`.`id` AS `b:id`, `b`.`a` AS `b:a`, `b`.`c` AS `b:c`, `b:c`.`id` AS `b:c:id`, `b:c`.`a` AS `b:c:a`, `b:c`.`c` AS `b:c:c` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphComplexWithGraphJoinedDeepNested(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $b;
            public $c;
            public $e;

            public static array $metadataCache =
            [
                'Model'=>[
                    ['Field'=>'id'],
                    ['Field'=>'b'],
                    ['Field'=>'c'],
                    ['Field'=>'e'],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static $relatedClass;

            public static function getRelationMappings(): array
            {
                return [
                    'b'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass'=>static::$relatedClass,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'c'=>[
                        'relation'=>Model::HAS_ONE_RELATION,
                        'modelClass'=>static::$relatedClass,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'e'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass'=>static::$relatedClass,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel::$relatedClass = $TestModel::class;

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('b.c.e')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`b` AS `b`, `Model`.`c` AS `c`, `Model`.`e` AS `e`, `b`.`id` AS `b:id`, `b`.`b` AS `b:b`, `b`.`c` AS `b:c`, `b`.`e` AS `b:e`, `b:c`.`id` AS `b:c:id`, `b:c`.`b` AS `b:c:b`, `b:c`.`c` AS `b:c:c`, `b:c`.`e` AS `b:c:e`, `b:c:e`.`id` AS `b:c:e:id`, `b:c:e`.`b` AS `b:c:e:b`, `b:c:e`.`c` AS `b:c:e:c`, `b:c:e`.`e` AS `b:c:e:e` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c:e` ON(`b:c:e`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphOverlappingWithGraphJoinedSimple(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static array $metadataCache =
            [
                'Model'=>[
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'b'],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'b'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[a, e]]')->allowGraph('b.c.[b, d]')->withGraphJoined('a')->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`b` AS `a:b` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphMultipleOverlappingWithGraphJoinedNested(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static array $metadataCache =
            [
                'Model'=>[
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'b'],
                ]
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                    'b'=>[
                        'relation' => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join' => [
                            'from' => 'Model.id',
                            'to' => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.c.[a, e]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('a.b')->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`b` AS `a:b`, `a:b`.`id` AS `a:b:id`, `a:b`.`a` AS `a:b:a`, `a:b`.`b` AS `a:b:b` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `a:b` ON(`a:b`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testNestedGraphFetchingWithSuccess(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;
            public $c;

            protected static array $metadataCache =
            [
                'Model' => [
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'c'],
                ],
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                    'c'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('a.c')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
                ['sql'=>'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`c` AS `c`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`c` AS `a:c`, `a:c`.`id` AS `a:c:id`, `a:c`.`a` AS `a:c:a`, `a:c`.`c` AS `a:c:c` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `a:c` ON(`a:c`.`id` = `Model`.`id`)', 'bindings'=>[]],
            ],
            $this->executedQueries
        );
    }

    public function testMultipleNestedGraphWithSuccess(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;
            public $b;

            protected static array $metadataCache =
            [
                'Model' => [
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'b'],
                ],
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                    'b'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('b.a')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
                ['sql'=>'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `b`.`id` AS `b:id`, `b`.`a` AS `b:a`, `b`.`b` AS `b:b`, `b:a`.`id` AS `b:a:id`, `b:a`.`a` AS `b:a:a`, `b:a`.`b` AS `b:a:b` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:a` ON(`b:a`.`id` = `Model`.`id`)', 'bindings'=>[]],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphNestedWithGraphJoinedDeeper(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;

            protected static array $metadataCache =
            [
                'Model' => [ ['Field'=>'id'], ],
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'b'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                    'c'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('b.c')->run();

        $this->assertEquals(
            [
                ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
                ['sql'=>'SELECT `Model`.`id` AS `id`, `b`.`id` AS `b:id`, `b:c`.`id` AS `b:c:id` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`)', 'bindings'=>[]],
            ],
            $this->executedQueries
        );
    }

    public function testMultipleGraphWithSpecificNestedGraphFetch(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $b;

            protected static array $metadataCache =
            [
                'Model' => [
                    ['Field'=>'id'],
                    ['Field'=>'b'],
                    ['Field'=>'c'],
                ],
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'b'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                    'c'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ]
                ];
            }
        };

        $this->executedQueries = [];

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('b.c.b')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['Model']],
                ['sql'=>'SELECT `Model`.`id` AS `id`, `Model`.`b` AS `b`, `Model`.`c` AS `c`, `b`.`id` AS `b:id`, `b`.`b` AS `b:b`, `b`.`c` AS `b:c`, `b:c`.`id` AS `b:c:id`, `b:c`.`b` AS `b:c:b`, `b:c`.`c` AS `b:c:c`, `b:c:b`.`id` AS `b:c:b:id`, `b:c:b`.`b` AS `b:c:b:b`, `b:c:b`.`c` AS `b:c:b:c` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c:b` ON(`b:c:b`.`id` = `Model`.`id`)', 'bindings'=>[]],
            ],
            $this->executedQueries
        );
    }

    public function testMismatchedGraphFetchShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;
            public $b;

            protected static array $metadataCache =
            [
                'Model' => [
                    ['Field'=>'id'],
                    ['Field'=>'a'],
                    ['Field'=>'b'],
                ],
            ];

            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'a'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                    'b'=>[
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>static::class,
                        'join'=>[
                            'from'=>'Model.id',
                            'to'=>'Model.id'
                        ],
                    ],
                ];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('a.b')->run();
    }

    public function testMismatchedGraphFetchWithMultipleAllowGraphShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->allowGraph('a.[c, d]')->withGraphJoined('a.b')->run();
    }

    public function testEagerFetchWithGraphShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->withGraphJoined('a.b')->allowGraph('[a, b.c.[d, e]]')->run();
    }

    public function testEagerFetchWithMultipleGraphShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->withGraphJoined('a.b')->allowGraph('[a, b.c.[d, e]]')->allowGraph('a.[c, d]')->run();
    }

    public function testDeeperEagerFetchWithGraphShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: b.c.d.e');

        $TestModel->query()->withGraphJoined('b.c.d.e')->allowGraph('[a, b.c.[d, e]]')->run();
    }

    public function testDeeperEagerFetchWithMultipleGraphShouldFail(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: b.c.d.e');

        $TestModel->query()->withGraphJoined('b.c.d.e')->allowGraph('[a, b.c.[d, e]]')->allowGraph('b.c.a')->run();
    }

    public function testGraphExpressionObjectShouldReturnEagerExpressionAsObject(): void
    {
        $this->markTestSkipped('Not implemented yet');
        return;

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->withGraphJoined('[a, b.c(foo)]');

        $expected = [
            '$name' => null,
            '$relation' => null,
            '$modify' => [],
            '$recursive' => false,
            '$allRecursive' => false,
            '$childNames' => ['a', 'b'],
            'a' => [
                '$name' => 'a',
                '$relation' => 'a',
                '$modify' => [],
                '$recursive' => false,
                '$allRecursive' => false,
                '$childNames' => [],
            ],
            'b' => [
                '$name' => 'b',
                '$relation' => 'b',
                '$modify' => [],
                '$recursive' => false,
                '$allRecursive' => false,
                '$childNames' => ['c'],
                'c' => [
                    '$name' => 'c',
                    '$relation' => 'c',
                    '$modify' => ['foo'],
                    '$recursive' => false,
                    '$allRecursive' => false,
                    '$childNames' => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $builder->graphExpressionObject());
    }

    public function testModifiersShouldReturnEagerExpressionsModifiersAsObject(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string { return 'Model'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $foo = function ($builder) {
            return $builder->where('foo');
        };

        $builder = $TestModel::query()->withGraphJoined('[a, b.c(foo)]')->modifiers(['foo' => $foo]);

        $this->assertEquals(['foo' => $foo], $builder->getModifiers());
    }

    public function testShouldUseCorrectSharQs(): void
    {
        $M1 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;

            public static function getTableName(): string { return 'M1'; }
            
            static $M2class;
            static $M1ModelSharQClass;

            protected static array $metadataCache =
            [
                'M1'=>[
                    ['Field'=>'id'],
                ]
            ];

            public static function getRelationMappings(): array
            {
                return [
                    'm2' => [
                        'relation' => static::HAS_MANY_RELATION,
                        'modelClass' => static::$M2class,
                        'join' => [
                            'from'=>'M1.id',
                            'to'=>'M2.m1Id'
                        ],
                    ],
                ];
            }
            
            public static function query($iTransactionOrClient=null): ModelSharQ
            {
                return new static::$M1ModelSharQClass(static::class);
            }
        };

        $M2 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $m1Id;

            public static function getTableName(): string { return 'M2'; }

            static $M3class;
            static $M2ModelSharQClass;

            protected static array $metadataCache =
            [
                'M2'=>[
                    ['Field'=>'id'],
                ]
            ];
            
            public static function getRelationMappings(): array
            {
                return [
                    'm3' => [
                        'relation' => static::BELONGS_TO_ONE_RELATION,
                        'modelClass' => static::$M3class,
                        'join' => [
                            'from'=>'M2.id',
                            'to'=>'M3.m2Id'
                        ],
                    ],
                ];
            }
            
            public static function query($iTransactionOrClient=null): ModelSharQ
            {
                return new static::$M2ModelSharQClass(static::class);
            }
        };

        $M3 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $m2Id;

            public static function getTableName(): string { return 'M3'; }
            
            static $M3ModelSharQClass;

            protected static array $metadataCache =
            [
                'M3'=>[
                    ['Field'=>'id'],
                ]
            ];

            public static function query($iTransactionOrClient=null): ModelSharQ
            {
                return new static::$M3ModelSharQClass(static::class);
            }
        };

        $M1ModelSharQ = new class($M1::class) extends \Sharksmedia\Qarium\ModelSharQ {};
        $M2ModelSharQ = new class($M2::class) extends \Sharksmedia\Qarium\ModelSharQ {};
        $M3ModelSharQ = new class($M3::class) extends \Sharksmedia\Qarium\ModelSharQ {};


        $M1::$M2class = $M2::class;
        $M1::$M1ModelSharQClass = $M1ModelSharQ::class;

        $M2::$M3class = $M3::class;
        $M2::$M2ModelSharQClass = $M2ModelSharQ::class;

        $M3::$M3ModelSharQClass = $M3ModelSharQ::class;


        $this->mockQueryResults = [
            [['id' => 1, 'm1Id' => 2, 'm3Id' => 3]],
            [['id' => 1, 'm1Id' => 2, 'm3Id' => 3]],
            [['id' => 1, 'm1Id' => 2, 'm3Id' => 3]],
        ];

        $filter1Check = false;
        $filter2Check = false;

        $M1::query()
            ->withGraphJoined('m2.m3')
            ->modifyGraph('m2', function($builder) use(&$filter1Check, $M2ModelSharQ)
                {
                    $filter1Check = $builder instanceof $M2ModelSharQ;
                })
            ->modifyGraph('m2.m3', function($builder) use(&$filter2Check, $M3ModelSharQ)
                {
                    $filter2Check = $builder instanceof $M3ModelSharQ;
                })
            ->run();

        $executedQueries = [
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['M1']],
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['M2']],
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['M3']],

            ['sql'=>'SELECT `M1`.`id` AS `id`, `m2`.`id` AS `m2:id`, `m2:m3`.`id` AS `m2:m3:id` FROM `M1` LEFT JOIN `M2` AS `m2` ON(`m2`.`m1Id` = `M1`.`id`) LEFT JOIN `M3` AS `m2:m3` ON(`m2:m3`.`m2Id` = `M2`.`id`)', 'bindings'=>[]],
        ];

        $this->assertEquals($executedQueries, $this->executedQueries);
        $this->assertTrue($filter1Check);
        $this->assertTrue($filter2Check);
    }

    public function testAfterFindShouldBeCalledAfterRelationsHaveBeenFetched(): void
    {
        $M1 = new class extends \Sharksmedia\Qarium\Model
        {
            public static $M1class;

            public $someRel = [];
            public $ids = [];

            public $id;
            public $m1Id;

            protected static array $metadataCache =
            [
                'M1'=>[
                    ['Field'=>'id'],
                    ['Field'=>'m1Id'],
                ]
            ];

            public static function getTableName(): string { return 'M1'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lafterFind($context) 
            {
                $this->ids = array_map(function($item)
                {
                    return $item->id;
                }, $this->someRel);

                return $context;
            }

            public static function getRelationMappings(): array
            {
                return [
                    'someRel' => [
                        'relation' => static::HAS_MANY_RELATION,
                        'modelClass' => static::$M1class,
                        'join' => [
                            'from'=>'M1.id',
                            'to'=>'M1.m1Id'
                        ],
                    ],
                ];
            }
        };

        $M1::$M1class = $M1::class;


        /*
        SELECT
            `M1`.`id` AS `id`,
            `M1`.`m1Id` AS `m1Id`,
            `someRel`.`id` AS `someRel:id`,
            `someRel`.`m1Id` AS `someRel:m1Id`,
            `someRel:someRel`.`id` AS `someRel:someRel:id`,
            `someRel:someRel`.`m1Id` AS `someRel:someRel:m1Id`
        FROM
            `M1`
            LEFT JOIN `M1` AS `someRel` ON `someRel`.`m1Id` = `M1`.`id`
            LEFT JOIN `M1` AS `someRel:someRel` ON `someRel:someRel`.`m1Id` = `someRel`.`id`
        */

        // Mocking database results
        $this->mockQueryResults =
        [
            [], // Metadata
            [
	            [ "id"=>1, "m1Id"=>null, "someRel:id"=>4, "someRel:m1Id"=>1, "someRel:someRel:id"=>10, "someRel:someRel:m1Id"=>4 ],
	            [ "id"=>1, "m1Id"=>null, "someRel:id"=>4, "someRel:m1Id"=>1, "someRel:someRel:id"=>9, "someRel:someRel:m1Id"=>4 ],
	            [ "id"=>1, "m1Id"=>null, "someRel:id"=>3, "someRel:m1Id"=>1, "someRel:someRel:id"=>8, "someRel:someRel:m1Id"=>3 ],
	            [ "id"=>1, "m1Id"=>null, "someRel:id"=>3, "someRel:m1Id"=>1, "someRel:someRel:id"=>7, "someRel:someRel:m1Id"=>3 ],
	            [ "id"=>2, "m1Id"=>null, "someRel:id"=>6, "someRel:m1Id"=>2, "someRel:someRel:id"=>14, "someRel:someRel:m1Id"=>6 ],
	            [ "id"=>2, "m1Id"=>null, "someRel:id"=>6, "someRel:m1Id"=>2, "someRel:someRel:id"=>13, "someRel:someRel:m1Id"=>6 ],
	            [ "id"=>2, "m1Id"=>null, "someRel:id"=>5, "someRel:m1Id"=>2, "someRel:someRel:id"=>12, "someRel:someRel:m1Id"=>5 ],
	            [ "id"=>2, "m1Id"=>null, "someRel:id"=>5, "someRel:m1Id"=>2, "someRel:someRel:id"=>11, "someRel:someRel:m1Id"=>5 ],
	            [ "id"=>3, "m1Id"=>1, "someRel:id"=>8, "someRel:m1Id"=>3, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>3, "m1Id"=>1, "someRel:id"=>7, "someRel:m1Id"=>3, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>4, "m1Id"=>1, "someRel:id"=>10, "someRel:m1Id"=>4, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>4, "m1Id"=>1, "someRel:id"=>9, "someRel:m1Id"=>4, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>5, "m1Id"=>2, "someRel:id"=>12, "someRel:m1Id"=>5, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>5, "m1Id"=>2, "someRel:id"=>11, "someRel:m1Id"=>5, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>6, "m1Id"=>2, "someRel:id"=>14, "someRel:m1Id"=>6, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>6, "m1Id"=>2, "someRel:id"=>13, "someRel:m1Id"=>6, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>7, "m1Id"=>3, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>8, "m1Id"=>3, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>9, "m1Id"=>4, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>10, "m1Id"=>4, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>11, "m1Id"=>5, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>12, "m1Id"=>5, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>13, "m1Id"=>6, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ],
	            [ "id"=>14, "m1Id"=>6, "someRel:id"=>null, "someRel:m1Id"=>null, "someRel:someRel:id"=>null, "someRel:someRel:m1Id"=>null ]
            ]
        ];

        // Execute query and verify
        $result = $M1::query()
            ->withGraphJoined('someRel.someRel')
            ->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals([
            ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = current_schema()', 'bindings'=>['M1']],
            ['sql'=>'SELECT `M1`.`id` AS `id`, `M1`.`m1Id` AS `m1Id`, `someRel`.`id` AS `someRel:id`, `someRel`.`m1Id` AS `someRel:m1Id`, `someRel:someRel`.`id` AS `someRel:someRel:id`, `someRel:someRel`.`m1Id` AS `someRel:someRel:m1Id` FROM `M1` LEFT JOIN `M1` AS `someRel` ON(`someRel`.`m1Id` = `M1`.`id`) LEFT JOIN `M1` AS `someRel:someRel` ON(`someRel:someRel`.`m1Id` = `M1`.`id`)', 'bindings'=>[]],
        ], $this->executedQueries);

        $this->assertEquals([
            (object) [
                'someRel' => [
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 10, 'm1Id' => 4],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 9, 'm1Id' => 4]
                        ],
                        'ids' => [],
                        'id' => 4,
                        'm1Id' => 1
                    ],
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 8, 'm1Id' => 3],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 7, 'm1Id' => 3]
                        ],
                        'ids' => [],
                        'id' => 3,
                        'm1Id' => 1
                    ]
                ],
                'ids' => [4, 3],
                'id' => 1,
                'm1Id' => null
            ],
            (object) [
                'someRel' => [
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 14, 'm1Id' => 6],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 13, 'm1Id' => 6]
                        ],
                        'ids' => [],
                        'id' => 6,
                        'm1Id' => 2
                    ],
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 12, 'm1Id' => 5],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 11, 'm1Id' => 5]
                        ],
                        'ids' => [],
                        'id' => 5,
                        'm1Id' => 2
                    ]
                ],
                'ids' => [6, 5],
                'id' => 2,
                'm1Id' => null
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 8, 'm1Id' => 3],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 7, 'm1Id' => 3]
                ],
                'ids' => [8, 7],
                'id' => 3,
                'm1Id' => 1
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 10, 'm1Id' => 4],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 9, 'm1Id' => 4]
                ],
                'ids' => [10, 9],
                'id' => 4,
                'm1Id' => 1
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 12, 'm1Id' => 5],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 11, 'm1Id' => 5]
                ],
                'ids' => [12, 11],
                'id' => 5,
                'm1Id' => 2
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 14, 'm1Id' => 6],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 13, 'm1Id' => 6]
                ],
                'ids' => [14, 13],
                'id' => 6,
                'm1Id' => 2
            ],
            (object) ['someRel' => [], 'ids' => [], 'id' => 7, 'm1Id' => 3],
            (object) ['someRel' => [], 'ids' => [], 'id' => 8, 'm1Id' => 3],
            (object) ['someRel' => [], 'ids' => [], 'id' => 9, 'm1Id' => 4],
            (object) ['someRel' => [], 'ids' => [], 'id' => 10, 'm1Id' => 4],
            (object) ['someRel' => [], 'ids' => [], 'id' => 11, 'm1Id' => 5],
            (object) ['someRel' => [], 'ids' => [], 'id' => 12, 'm1Id' => 5],
            (object) ['someRel' => [], 'ids' => [], 'id' => 13, 'm1Id' => 6],
            (object) ['someRel' => [], 'ids' => [], 'id' => 14, 'm1Id' => 6]
        ], json_decode(json_encode($result)));
    }

    public function testContextShouldMergeContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1])->context(['b' => 2]);

        global $MockMySQLClient;

        $this->assertEquals(['a' => 1, 'b' => 2], $builder->context()->getInternalData());
        $this->assertTrue($builder->getContext()->transaction === $MockMySQLClient);
    }

    public function testClearContextShouldClearTheContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1])->clearContext();
        $this->assertEquals([], $builder->getContext()->userContext->getInternalData());
    }

    public function testContextWithoutPreviousContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1])->context(['b' => 2]);

        global $MockMySQLClient;

        $this->assertEquals(['a' => 1, 'b' => 2], $builder->getContext()->userContext->getInternalData());
        $this->assertTrue($builder->getContext()->transaction === $MockMySQLClient);
    }

    public function testCloningSharQShouldCloneContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1]);

        $builder2 = clone $builder;
        $builder2->context(['b' => 2]);

        $this->assertEquals(['a' => 1], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }

    public function testChildQueryOfShouldCopyReferenceOfContext(): void
    {
        $this->markTestSkipped('This test is not working yet.');

        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1]);
        $builder2 = $TestModel::query()->childQueryOf($builder)->context(['b' => 2]);

        $this->assertEquals(['a' => 1, 'b' => 2], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }

    public function testChildQueryOfWithForkOptionShouldCopyContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $builder = $TestModel::query()->context(['a' => 1]);
        $builder2 = $TestModel::query()->childQueryOf($builder, true)->context(['b' => 2]);

        $this->assertEquals(['a' => 1], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }

    public function testValuesSavedToContextInHooksShouldBeAvailableLater(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model 
        {
            public $a = null;
            public static $foo = null;

            public static function getTableName(): string { return 'TestModel'; }
            public static function getTableIDs(): array { return ['id']; }

            public function lbeforeUpdate($context): void
            {
                $context->foo = 101;
            }

            public function lafterUpdate($context): void 
            {
                static::$foo = $context->foo;
            }
        };

        $instance = $TestModel::query()->patch(['a' => 1])->run();
        $this->assertEquals(101, $TestModel::$foo);
    }

    public function testQueryUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model 
        {
            public $id;
            public $foo;

            public static function getTableName(): string { return 'person'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $query = $person::query()->update(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql = $query->toFindQuery()->toString();

        $this->assertEquals('SELECT `person`.* FROM `person` WHERE `name` LIKE ?', $sql);
    }

    public function testRelatedQueryHasManyUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model 
        {
            public $id;

            public static $petClass;
            
            public static function getTableName(): string { return 'person'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'pets' => [
                        'relation'=>self::HAS_MANY_RELATION,
                        'modelClass'=>self::$petClass,
                        'join' => [
                            'from'=>'person.id',
                            'to'=>'pet.owner_id'
                        ]
                    ]
                ];
            }
        };

        $pet = new class extends \Sharksmedia\Qarium\Model 
        {
            public $id;
            public $owner_id;
            public $foo;

            public static function getTableName(): string { return 'pet'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $person::$petClass = $pet::class;

        $instance = $person::createFromDatabaseArray(['id' => 1]);

        $query = $instance->lrelatedQuery('pets')->update(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql = $query->toFindQuery()->toString();
        
        $this->assertEquals('SELECT `pet`.* FROM `pet` WHERE `pet`.`owner_id` IN(?) AND `name` LIKE ?', $sql);
    }

    public function testRelatedQueryBelongsToOneUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model 
        {
            public $id;
            public $foo;
            
            public static function getTableName(): string { return 'person'; }
            public static function getTableIDs(): array { return ['id']; }
        };

        $pet = new class extends \Sharksmedia\Qarium\Model 
        {
            public $id;
            public $owner_id;

            public static $personClass;

            public static function getTableName(): string { return 'pet'; }
            public static function getTableIDs(): array { return ['id']; }

            public static function getRelationMappings(): array
            {
                return [
                    'owner'=> [
                        'relation'=>self::BELONGS_TO_ONE_RELATION,
                        'modelClass'=>self::$personClass,
                        'join'=>[
                            'from'=>'pet.owner_id',
                            'to'=>'person.id'
                        ]
                    ]
                ];
            }
        };

        $pet::$personClass = $person::class;

        $instance = $pet::createFromDatabaseArray(['owner_id' => 1]);
        $query = $instance->lrelatedQuery('owner')->patch(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql = $query->toFindQuery()->toString();
        
        $this->assertEquals('SELECT `person`.* FROM `person` WHERE `person`.`id` IN(?) AND `name` LIKE ?', $sql);
    }
}
