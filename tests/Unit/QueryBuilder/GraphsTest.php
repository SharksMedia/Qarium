<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Tests\Support\TQueryBuilder;

class GraphsTest extends Unit
{
    use TQueryBuilder;

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
                'Model' => [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'b'],
                    ['COLUMN_NAME' => 'c'],
                    ['COLUMN_NAME' => 'e'],
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

            public static $relatedClass;

            public static function getRelationMappings(): array
            {
                return [
                    'b' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::$relatedClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'c' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::$relatedClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'e' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::$relatedClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
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
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`b` AS `b`, `Model`.`c` AS `c`, `Model`.`e` AS `e`, `b`.`id` AS `b:id`, `b`.`b` AS `b:b`, `b`.`c` AS `b:c`, `b`.`e` AS `b:e`, `b:c`.`id` AS `b:c:id`, `b:c`.`b` AS `b:c:b`, `b:c`.`c` AS `b:c:c`, `b:c`.`e` AS `b:c:e`, `b:c:e`.`id` AS `b:c:e:id`, `b:c:e`.`b` AS `b:c:e:b`, `b:c:e`.`c` AS `b:c:e:c`, `b:c:e`.`e` AS `b:c:e:e` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c:e` ON(`b:c:e`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
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
                'Model' => [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'a'],
                    ['COLUMN_NAME' => 'c'],
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
                return [
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'b' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'c' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('b.c')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`c` AS `c`, `b`.`id` AS `b:id`, `b`.`a` AS `b:a`, `b`.`c` AS `b:c`, `b:c`.`id` AS `b:c:id`, `b:c`.`a` AS `b:c:a`, `b:c`.`c` AS `b:c:c` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphComplexWithGraphJoinedSimple(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static array $metadataCache =
            [
                'Model' => [ ['COLUMN_NAME' => 'a'] ]
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
                return [
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('a')->run();
    }


    public function testAllowGraphMultipleOverlappingWithGraphJoinedNested(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

            public static array $metadataCache =
            [
                'Model' => [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'a'],
                    ['COLUMN_NAME' => 'b'],
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
                return [
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'b' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.c.[a, e]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('a.b')->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`b` AS `a:b`, `a:b`.`id` AS `a:b:id`, `a:b`.`a` AS `a:b:a`, `a:b`.`b` AS `a:b:b` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `a:b` ON(`a:b`.`id` = `Model`.`id`)', 'bindings' => []],
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
                'Model' => [ ['COLUMN_NAME' => 'id'], ],
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'b' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ],
                    'c' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('b.c')->run();

        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `b`.`id` AS `b:id`, `b:c`.`id` AS `b:c:id` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`)', 'bindings' => []],
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
                'Model' => [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'a'],
                    ['COLUMN_NAME' => 'b'],
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
                return [
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                    'b' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ],
                ];
            }
        };

        $TestModel->query()->allowGraph('[a, b.c.[a, e]]')->allowGraph('b.c.[b, d]')->withGraphJoined('a')->run();

        $this->assertCount(2, $this->executedQueries);

        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`b` AS `a:b` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testAllowGraphWithSingleFunctionJoined(): void
    {
        $this->markTestSkipped('Not implemented yet');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

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
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('a')->withGraphJoined('a(f1)', ['f1' => function ()
        {}])->run();

        $this->assertCount(1, $this->executedQueries);
    }


    public function testDeeperEagerFetchWithGraphShouldFail(): void
    {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: b.c.d.e');

        $TestModel->query()->withGraphJoined('b.c.d.e')->allowGraph('[a, b.c.[d, e]]')->run();
    }


    public function testDeeperEagerFetchWithMultipleGraphShouldFail(): void
    {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: b.c.d.e');

        $TestModel->query()->withGraphJoined('b.c.d.e')->allowGraph('[a, b.c.[d, e]]')->allowGraph('b.c.a')->run();
    }

    public function testEagerFetchWithGraphShouldFail(): void
    {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->withGraphJoined('a.b')->allowGraph('[a, b.c.[d, e]]')->run();
    }


    public function testEagerFetchWithMultipleGraphShouldFail(): void
    {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->withGraphJoined('a.b')->allowGraph('[a, b.c.[d, e]]')->allowGraph('a.[c, d]')->run();
    }


    public function testMismatchedGraphFetchWithMultipleAllowGraphShouldFail(): void
    {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->allowGraph('a.[c, d]')->withGraphJoined('a.b')->run();
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'b'],
                    ['COLUMN_NAME' => 'c'],
                ],
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
                return [
                    'b' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ],
                    'c' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
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
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`b` AS `b`, `Model`.`c` AS `c`, `b`.`id` AS `b:id`, `b`.`b` AS `b:b`, `b`.`c` AS `b:c`, `b:c`.`id` AS `b:c:id`, `b:c`.`b` AS `b:c:b`, `b:c`.`c` AS `b:c:c`, `b:c:b`.`id` AS `b:c:b:id`, `b:c:b`.`b` AS `b:c:b:b`, `b:c:b`.`c` AS `b:c:b:c` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c` ON(`b:c`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:c:b` ON(`b:c:b`.`id` = `Model`.`id`)', 'bindings' => []],
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'a'],
                    ['COLUMN_NAME' => 'b'],
                ],
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'a' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ],
                    'b' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('b.a')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`b` AS `b`, `b`.`id` AS `b:id`, `b`.`a` AS `b:a`, `b`.`b` AS `b:b`, `b:a`.`id` AS `b:a:id`, `b:a`.`a` AS `b:a:a`, `b:a`.`b` AS `b:a:b` FROM `Model` LEFT JOIN `Model` AS `b` ON(`b`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `b:a` ON(`b:a`.`id` = `Model`.`id`)', 'bindings' => []],
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'a'],
                    ['COLUMN_NAME' => 'c'],
                ],
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static $aclass;

            public static function getRelationMappings(): array
            {
                return [
                    'a' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ],
                    'c' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id'
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->allowGraph('[a.[a, b], b.[a, c]]')->allowGraph('[a.[c, d], b.c.[b, d]]')->withGraphJoined('a.c')->run();

        $this->assertCount(2, $this->executedQueries);
        $this->assertEquals(
            [
                ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['Model']],
                ['sql' => 'SELECT `Model`.`id` AS `id`, `Model`.`a` AS `a`, `Model`.`c` AS `c`, `a`.`id` AS `a:id`, `a`.`a` AS `a:a`, `a`.`c` AS `a:c`, `a:c`.`id` AS `a:c:id`, `a:c`.`a` AS `a:c:a`, `a:c`.`c` AS `a:c:c` FROM `Model` LEFT JOIN `Model` AS `a` ON(`a`.`id` = `Model`.`id`) LEFT JOIN `Model` AS `a:c` ON(`a:c`.`id` = `Model`.`id`)', 'bindings' => []],
            ],
            $this->executedQueries
        );
    }

    public function testWithGraphJoinedAllowGraphOrder(): void
    {
        $this->markTestSkipped('Not implemented yet');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $a;

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
                    'a' => [
                        'relation'   => Model::HAS_ONE_RELATION,
                        'modelClass' => static::class,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'Model.id',
                        ],
                    ]
                ];
            }
        };

        $TestModel->query()->withGraphJoined('a(f1)', ['f1' => function ()
        {}])->allowGraph('a')->run();

        $this->assertCount(1, $this->executedQueries);
    }

    public function testGraphExpressionObjectShouldReturnEagerExpressionAsObject(): void
    {
        $this->markTestSkipped('Not implemented yet');

        return;

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

        $builder = $TestModel::query()->withGraphJoined('[a, b.c(foo)]');

        $expected = [
            '$name'         => null,
            '$relation'     => null,
            '$modify'       => [],
            '$recursive'    => false,
            '$allRecursive' => false,
            '$childNames'   => ['a', 'b'],
            'a'             => [
                '$name'         => 'a',
                '$relation'     => 'a',
                '$modify'       => [],
                '$recursive'    => false,
                '$allRecursive' => false,
                '$childNames'   => [],
            ],
            'b' => [
                '$name'         => 'b',
                '$relation'     => 'b',
                '$modify'       => [],
                '$recursive'    => false,
                '$allRecursive' => false,
                '$childNames'   => ['c'],
                'c'             => [
                    '$name'         => 'c',
                    '$relation'     => 'c',
                    '$modify'       => ['foo'],
                    '$recursive'    => false,
                    '$allRecursive' => false,
                    '$childNames'   => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $builder->graphExpressionObject());
    }
}
