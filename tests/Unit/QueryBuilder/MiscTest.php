<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQBase;
use Sharksmedia\SharQ\Client;
use Sharksmedia\SharQ\SharQ;
use Tests\Support\TQueryBuilder;

class MiscTest extends Unit
{
    use TQueryBuilder;

    public function testShouldHaveSharQMethods(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };
        
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

        foreach ($queryBuilderMethods as $qbMethodName)
        {
            if (!in_array($qbMethodName, $ignoreMethods, true))
            {
                // if(!method_exists($builder, $qbMethodName)) $missingFunctions[] = $qbMethodName;

                $this->assertTrue(method_exists($builder, $qbMethodName), "SharQ method '".$qbMethodName."' is missing from ModelSharQ");
            }
        }
    }

    public function testShouldHaveSharQMethods2(): void
    {
        // Doesn't test all the methods. Just enough to make sure the method calls are correctly
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testFirstShouldAddLimit1IfModelUseLimitInFirstIsTrue(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public const USE_LIMIT_IN_FIRST = true;

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $TestModel::query()->first()->run();
        $this->assertEquals(['sql' => 'SELECT `Model`.* FROM `Model` LIMIT ?', 'bindings' => [1]], $this->executedQueries[0]);
    }


    public function testFirstShouldNotAddLimit1ByDefault(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $TestModel::query()->first()->run();
        $this->assertEquals(['sql' => 'SELECT `Model`.* FROM `Model`', 'bindings' => []], $this->executedQueries[0]);
    }


    public function testPageShouldReturnAPageAndTheTotalCount()
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $a = null;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['a' => '2']], [['count' => '123']]];

        $res = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->page(10, 100)
            ->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals([
            ['sql' => 'SELECT `Model`.* FROM `Model` WHERE `test` = ? ORDER BY `order` ASC LIMIT ? OFFSET ?', 'bindings' => [100, 100, 1000]],
            ['sql' => 'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`', 'bindings' => [100]],
        ], $this->executedQueries);

        $iResultTestModel    = new $TestModel();
        $iResultTestModel->a = '2';

        $this->assertEquals(123, $res['total']);
        $this->assertEquals([$iResultTestModel], $res['results']);
    }


    public function testParseRelationExpression(): void
    {
        $this->markTestSkipped('Not implemented yet');

        return;

        $parsed = ModelSharQ::parseRelationExpression('[foo, bar.baz]');

        $expected = [
            '$name'         => null,
            '$relation'     => null,
            '$modify'       => [],
            '$recursive'    => false,
            '$allRecursive' => false,
            '$childNames'   => ['foo', 'bar'],
            'foo'           => [
                '$name'         => 'foo',
                '$relation'     => 'foo',
                '$modify'       => [],
                '$recursive'    => false,
                '$allRecursive' => false,
                '$childNames'   => [],
            ],
            'bar' => [
                '$name'         => 'bar',
                '$relation'     => 'bar',
                '$modify'       => [],
                '$recursive'    => false,
                '$allRecursive' => false,
                '$childNames'   => ['baz'],
                'baz'           => [
                    '$name'         => 'baz',
                    '$relation'     => 'baz',
                    '$modify'       => [],
                    '$recursive'    => false,
                    '$allRecursive' => false,
                    '$childNames'   => [],
                ],
            ],
        ];
        
        $this->assertEquals($expected, $parsed);
    }


    public function testRangeShouldReturnARangeAndTheTotalCount()
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $a = null;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['a' => '1']], [['count' => '123']]];

        $res = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->range(100, 200)
            ->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals([
            ['sql' => 'SELECT `Model`.* FROM `Model` WHERE `test` = ? ORDER BY `order` ASC LIMIT ? OFFSET ?', 'bindings' => [100, 101, 100]],
            ['sql' => 'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`', 'bindings' => [100]],
        ], $this->executedQueries);

        $iResultTestModel    = new $TestModel();
        $iResultTestModel->a = '1';

        $this->assertEquals(123, $res['total']);
        $this->assertEquals([$iResultTestModel], $res['results']);
    }


    public function testShouldBeAbleToExecuteSameQueryMultipleTimes(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
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

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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


    public function testShouldCallCustomDeleteImplementationDefinedByDeleteOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->deleteOperationFactory(function($iBuilder)
            {
                return self::createDeleteOperation($iBuilder, ['id' => 100]);
            })
            ->delete()
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model` WHERE `id` = ?', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallCustomFindImplementationDefinedByFindOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [
                        'COLUMN_NAME' => 'a',
                    ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testShouldCallCustomInsertImplementationDefinedByInsertOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->insertOperationFactory(function($iBuilder)
            {
                return self::createInsertOperation($iBuilder, ['b' => 2]);
            })
            ->insert(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('INSERT INTO `Model` (`a`, `b`) VALUES (?, ?)', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallCustomPatchImplementationDefinedByPatchOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->patchOperationFactory(function ($iBuilder)
            {
                return self::createUpdateOperation($iBuilder, ['b' => 2]);
            })
            ->patch(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ?', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallCustomRelateImplementationDefinedByRelateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->relateOperationFactory(function($iBuilder)
            {
                return self::createInsertOperation($iBuilder, ['b' => 2]);
            })
            ->relate(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('INSERT INTO `Model` (`a`, `b`) VALUES (?, ?)', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallCustomUnrelateImplementationDefinedByUnrelateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->unrelateOperationFactory(function($iBuilder)
            {
                return self::createDeleteOperation($iBuilder, ['id' => 100]);
            })
            ->unrelate()
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('DELETE FROM `Model` WHERE `id` = ?', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallCustomUpdateImplementationDefinedByUpdateOperationFactory(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a = null;
            public ?int $b = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries = [];

        $result = ModelSharQ::forClass($TestModel::class)
            ->updateOperationFactory(function ($iBuilder)
            {
                return self::createUpdateOperation($iBuilder, ['b' => 2]);
            })
            ->update(['a' => 1])
            ->run();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals('UPDATE `Model` SET `a` = ?, `b` = ?', $this->executedQueries[0]['sql']);
    }


    public function testShouldCallRunMethodsInTheCorrectOrder(): void
    {
        $this->mockQueryResults = [[['a' => 0]]];

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [
                        'COLUMN_NAME' => 'a',
                    ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $res = 0;

        ModelSharQ::forClass($TestModel::class)
            ->runBefore(function() use (&$res)
            {
                $this->assertEquals(0, $res);
                ++$res;
            })
            ->runBefore(function() use (&$res)
            {
                $this->assertEquals(1, $res);
                ++$res;
            })
            ->runBefore(function() use (&$res)
            {
                $this->assertEquals(2, $res);
                ++$res;
            })
            ->runAfter(function($builder) use (&$res)
            {
                $this->assertEquals(3, $res);

                // Assuming there's a delay or wait function available in PHP
                return ++$res;
            })
            ->runAfter(function($builder) use (&$res)
            {
                $this->assertEquals(4, $res);

                return ++$res;
            })
            ->run();

        $this->assertEquals(5, $res);
    }


    public function testShouldConsiderWithSchemaWhenLookingForColumnInfo(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?int $id = null;
            // protected ?string $count=null;
            
            public static $RelatedClass = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'id', ],
                    // [ 'COLUMN_NAME'=>'count' ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }

            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                $mappings =
                [
                    'iRelated' =>
                    [
                        'relation'   => Model::BELONGS_TO_ONE_RELATION,
                        'modelClass' => static::$RelatedClass::class,
                        'join'       =>
                        [
                            'from' => 'Model.id',
                            'to'   => 'Related.id',
                        ],
                    ]
                ];

                return $mappings;
            }

            public static function fetchTableMetadata(?Client $iClient = null, ?string $schema = null): array
            {
                parent::fetchTableMetadata(); // Just to get the query executed

                return static::$metadataCache;
            }
        };

        $TestModelRelated = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?int $id = null;

            protected static array $metadataCache =
            [
                'Related' =>
                [
                    [ 'COLUMN_NAME' => 'id' ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Related';
            }

            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function fetchTableMetadata(?Client $iClient = null, ?string $schema = null): array
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
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Related']],
            ['sql' => 'SELECT `Model`.`id` AS `id`, `iRelated`.`id` AS `iRelated:id` FROM `someSchema`.`Model` LEFT JOIN `someSchema`.`Related` AS `iRelated` ON(`iRelated`.`id` = `Model`.`id`)', 'bindings' => []],
        ];

        $this->assertCount(3, $this->executedQueries);
        $this->assertEquals($expectedQueries, $this->executedQueries);
    }


    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfDeleteIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [
                        'COLUMN_NAME' => 'a',
                    ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testShouldNotCallCustomFindImplementationDefinedByFindOperationFactoryIfInsertIsCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [
                        'COLUMN_NAME' => 'a',
                    ]
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->executedQueries  = [];
        $this->mockQueryResults = [[1]];

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
                'Model' =>
                [
                    [
                        'COLUMN_NAME' => 'a',
                    ]
                ]
            ];

            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
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

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['a' => 1]]];

        $text = '';

        $exception = new \Exception('abort');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('abort');

        ModelSharQ::forClass($TestModel::class)
            ->runBefore(function(ModelSharQ $builder) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $builder);
                $text .= 'a';
            })
            ->onBuild(function(ModelSharQ $builder) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $builder);
                $text .= 'b';
            })
            ->onBuildSharQ(function($iBuilder, $iSharQ) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $iBuilder);
                // Assuming isSharQ() is equivalent to checking if $iSharQ is an instance of a specific class
                $this->assertInstanceOf(SharQ::class, $iSharQ);
                $text .= 'c';
            })
            ->runAfter(function($builder, ?array $data) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $builder);
                $text .= 'd';
            })
            ->runAfter(function($builder, ?array $data) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $builder);
                $text .= 'e';
            })
            ->runAfter(function() use ($exception)
            {
                throw $exception;
            })
            ->onError(function($builder, \Exception $err) use (&$text)
            {
                $this->assertInstanceOf(ModelSharQ::class, $builder);
                $this->assertEquals('abort', $err->getMessage());
                $text .= 'f';
            })
            ->run();

        $this->assertEquals('abcdef', $text);
    }


    public function testShouldReturnASharQFromTimeoutMethod(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $builder = ModelSharQ::forClass($TestModel::class)->timeout(3000);

        $this->assertInstanceOf(ModelSharQ::class, $builder);
    }


    public function testShouldSelectAllFromTheModelTableIfNoQueryMethodsAreCalled(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $iModelSharQ = ModelSharQ::forClass($TestModel::class);

        $this->assertEquals('SELECT `Model`.* FROM `Model`', $iModelSharQ->toSQL());
    }

    public function testOperationTypeMethodsShouldReturnTrueOnlyForTheRightOperations()
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $id = null;

            public static $RelatedTestModelClass = null;

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'someRel' => [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => static::$RelatedTestModelClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId = null;

            public static function getTableName(): string
            {
                return 'ModelRelation';
            }
            public static function getTableIDs(): array
            {
                return ['someRelId'];
            }
        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;

        $queries = [
            'find'     => $TestModel::query(),
            'insert'   => $TestModel::query()->insert([]),
            'update'   => $TestModel::query()->update([]),
            'patch'    => $TestModel::query()->patch([]),
            'delete'   => $TestModel::query()->delete(),
            'relate'   => $TestModel::relatedQuery('someRel')->relate(1),
            'unrelate' => $TestModel::relatedQuery('someRel')->unrelate(),
        ];

        $getMethodName = function ($name)
        {
            return 'is'.ucfirst($name === 'patch' ? 'update' : $name);
        };

        foreach ($queries as $name => $query)
        {
            foreach ($queries as $other => $_)
            {
                $method = $getMethodName($other);
                $this->assertEquals($method === $getMethodName($name), $query->$method(), "queries['$name']->$method()");
                $this->assertEquals(str_contains($name, 'relate'), $query->hasWheres(), "queries['$name']->hasWheres()");
                $this->assertFalse($query->hasSelects(), "queries['$name']->hasSelects()");
            }
        }
    }

    public function testClearShouldRemoveMatchingQueryOperations(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $operations = ['where', 'limit', 'offset', 'count'];

        foreach ($operations as $operation)
        {
            $query = $TestModel::query();

            foreach ($operations as $operationToApply)
            {
                $query->$operationToApply('arg');
            }

            $this->assertTrue($query->has($operation), "query()->has('$operation')");
            $this->assertFalse($query->clear($operation)->has($operation), "query()->clear('$operation')->has('$operation')");

            foreach ($operations as $testOperation)
            {
                $this->assertEquals($testOperation !== $operation, $query->has($testOperation), "query()->has('$testOperation')");
            }
        }
    }

    public function testClearWithGraphShouldClearEverythingRelatedToEager(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $SharQ = ModelSharQ::forClass($TestModel::class)
            ->withGraphJoined('a(f).b', ['f' => function()
            {}])
            ->modifyGraph('a', function()
            {});

        $this->assertNotNull($SharQ->findOperation('eager'));
        $SharQ->clearWithGraph();
        $this->assertNull($SharQ->findOperation('eager'));
    }

    public function testClearRejectShouldClearRemoveExplicitRejection(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $SharQ = ModelSharQ::forClass($TestModel::class);
        $SharQ->reject('error');


        $this->assertEquals('error', $SharQ->getExplicitRejectValue());
        $SharQ->clearReject();
        $this->assertNull($SharQ->getExplicitRejectValue());
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'foo']
                ],
            ];

            public static function getTableName(): string
            {
                return 'Bar';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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

    public function testAfterFindShouldBeCalledAfterRelationsHaveBeenFetched(): void
    {
        $M1 = new class extends \Sharksmedia\Qarium\Model
        {
            public static $M1class;

            public $someRel = [];
            public $ids     = [];

            public $id;
            public $m1Id;

            protected static array $metadataCache =
            [
                'M1' => [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'm1Id'],
                ]
            ];

            public static function getTableName(): string
            {
                return 'M1';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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
                        'relation'   => static::HAS_MANY_RELATION,
                        'modelClass' => static::$M1class,
                        'join'       => [
                            'from' => 'M1.id',
                            'to'   => 'M1.m1Id'
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
                [ "id" => 1, "m1Id" => null, "someRel:id" => 4, "someRel:m1Id" => 1, "someRel:someRel:id" => 10, "someRel:someRel:m1Id" => 4 ],
                [ "id" => 1, "m1Id" => null, "someRel:id" => 4, "someRel:m1Id" => 1, "someRel:someRel:id" => 9, "someRel:someRel:m1Id" => 4 ],
                [ "id" => 1, "m1Id" => null, "someRel:id" => 3, "someRel:m1Id" => 1, "someRel:someRel:id" => 8, "someRel:someRel:m1Id" => 3 ],
                [ "id" => 1, "m1Id" => null, "someRel:id" => 3, "someRel:m1Id" => 1, "someRel:someRel:id" => 7, "someRel:someRel:m1Id" => 3 ],
                [ "id" => 2, "m1Id" => null, "someRel:id" => 6, "someRel:m1Id" => 2, "someRel:someRel:id" => 14, "someRel:someRel:m1Id" => 6 ],
                [ "id" => 2, "m1Id" => null, "someRel:id" => 6, "someRel:m1Id" => 2, "someRel:someRel:id" => 13, "someRel:someRel:m1Id" => 6 ],
                [ "id" => 2, "m1Id" => null, "someRel:id" => 5, "someRel:m1Id" => 2, "someRel:someRel:id" => 12, "someRel:someRel:m1Id" => 5 ],
                [ "id" => 2, "m1Id" => null, "someRel:id" => 5, "someRel:m1Id" => 2, "someRel:someRel:id" => 11, "someRel:someRel:m1Id" => 5 ],
                [ "id" => 3, "m1Id" => 1, "someRel:id" => 8, "someRel:m1Id" => 3, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 3, "m1Id" => 1, "someRel:id" => 7, "someRel:m1Id" => 3, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 4, "m1Id" => 1, "someRel:id" => 10, "someRel:m1Id" => 4, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 4, "m1Id" => 1, "someRel:id" => 9, "someRel:m1Id" => 4, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 5, "m1Id" => 2, "someRel:id" => 12, "someRel:m1Id" => 5, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 5, "m1Id" => 2, "someRel:id" => 11, "someRel:m1Id" => 5, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 6, "m1Id" => 2, "someRel:id" => 14, "someRel:m1Id" => 6, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 6, "m1Id" => 2, "someRel:id" => 13, "someRel:m1Id" => 6, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 7, "m1Id" => 3, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 8, "m1Id" => 3, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 9, "m1Id" => 4, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 10, "m1Id" => 4, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 11, "m1Id" => 5, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 12, "m1Id" => 5, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 13, "m1Id" => 6, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ],
                [ "id" => 14, "m1Id" => 6, "someRel:id" => null, "someRel:m1Id" => null, "someRel:someRel:id" => null, "someRel:someRel:m1Id" => null ]
            ]
        ];

        // Execute query and verify
        $result = $M1::query()
            ->withGraphJoined('someRel.someRel')
            ->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals([
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['M1']],
            ['sql' => 'SELECT `M1`.`id` AS `id`, `M1`.`m1Id` AS `m1Id`, `someRel`.`id` AS `someRel:id`, `someRel`.`m1Id` AS `someRel:m1Id`, `someRel:someRel`.`id` AS `someRel:someRel:id`, `someRel:someRel`.`m1Id` AS `someRel:someRel:m1Id` FROM `M1` LEFT JOIN `M1` AS `someRel` ON(`someRel`.`m1Id` = `M1`.`id`) LEFT JOIN `M1` AS `someRel:someRel` ON(`someRel:someRel`.`m1Id` = `M1`.`id`)', 'bindings' => []],
        ], $this->executedQueries);

        $this->assertEquals([
            (object) [
                'someRel' => [
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 10, 'm1Id' => 4],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 9, 'm1Id' => 4]
                        ],
                        'ids'  => [],
                        'id'   => 4,
                        'm1Id' => 1
                    ],
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 8, 'm1Id' => 3],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 7, 'm1Id' => 3]
                        ],
                        'ids'  => [],
                        'id'   => 3,
                        'm1Id' => 1
                    ]
                ],
                'ids'  => [4, 3],
                'id'   => 1,
                'm1Id' => null
            ],
            (object) [
                'someRel' => [
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 14, 'm1Id' => 6],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 13, 'm1Id' => 6]
                        ],
                        'ids'  => [],
                        'id'   => 6,
                        'm1Id' => 2
                    ],
                    (object) [
                        'someRel' => [
                            (object) ['someRel' => [], 'ids' => [], 'id' => 12, 'm1Id' => 5],
                            (object) ['someRel' => [], 'ids' => [], 'id' => 11, 'm1Id' => 5]
                        ],
                        'ids'  => [],
                        'id'   => 5,
                        'm1Id' => 2
                    ]
                ],
                'ids'  => [6, 5],
                'id'   => 2,
                'm1Id' => null
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 8, 'm1Id' => 3],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 7, 'm1Id' => 3]
                ],
                'ids'  => [8, 7],
                'id'   => 3,
                'm1Id' => 1
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 10, 'm1Id' => 4],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 9, 'm1Id' => 4]
                ],
                'ids'  => [10, 9],
                'id'   => 4,
                'm1Id' => 1
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 12, 'm1Id' => 5],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 11, 'm1Id' => 5]
                ],
                'ids'  => [12, 11],
                'id'   => 5,
                'm1Id' => 2
            ],
            (object) [
                'someRel' => [
                    (object) ['someRel' => [], 'ids' => [], 'id' => 14, 'm1Id' => 6],
                    (object) ['someRel' => [], 'ids' => [], 'id' => 13, 'm1Id' => 6]
                ],
                'ids'  => [14, 13],
                'id'   => 6,
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
}
