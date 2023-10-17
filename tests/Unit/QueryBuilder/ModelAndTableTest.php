<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Tests\Support\TQueryBuilder;

class ModelAndTableTest extends Unit
{
    use TQueryBuilder;


    public function testModelClassShouldReturnTheModelClass(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };

        $this->assertEquals($TestModel::class, ModelSharQ::forClass($TestModel::class)->getModelClass());
    }


    public function testTableNameForShouldReturnTheTableName(): void
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
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $query = $TestModel::query()->from('Lol');
        $this->assertEquals('Lol', $query->getTableNameFor($TestModel::class));
    }


    public function testTableRefForShouldReturnTheAlias(): void
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

        $query = $TestModel::query()->alias('Lyl');
        $this->assertEquals('Lyl', $query->getTableRefFor($TestModel::class));
    }


    public function testTableRefForShouldReturnTheTableNameByDefault(): void
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

        $query = $TestModel::query();
        $this->assertEquals('Model', $query->getTableRefFor($TestModel::class));
    }


    public function testShouldConvertAnObjectQueryResultIntoAModelInstance(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['a' => 1]]];

        $result = ModelSharQ::forClass($TestModel::class)
            ->first()
            ->run();

        $this->assertInstanceOf($TestModel::class, $result);
        $this->assertEquals(1, $result->a);
    }


    public function testShouldConvertArrayQueryResultIntoModelInstances(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public int $a;

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['a' => 1], ['a' => 2]]];

        $results = ModelSharQ::forClass($TestModel::class)->run();

        $this->assertCount(2, $results);
        $this->assertInstanceOf($TestModel::class, $results[0]);
        $this->assertInstanceOf($TestModel::class, $results[1]);
        // $this->assertEquals($this->mockQueryResults, $results); // This assertion makes no sense. The results should be of type Model, not array.
    }


    public function testShouldUseCorrectSharQs(): void
    {
        $M1 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;

            public static function getTableName(): string
            {
                return 'M1';
            }
            
            public static $M2class;
            public static $M1ModelSharQClass;

            protected static array $metadataCache =
            [
                'M1' => [
                    ['COLUMN_NAME' => 'id'],
                ]
            ];

            public static function getRelationMappings(): array
            {
                return [
                    'm2' => [
                        'relation'   => static::HAS_MANY_RELATION,
                        'modelClass' => static::$M2class,
                        'join'       => [
                            'from' => 'M1.id',
                            'to'   => 'M2.m1Id'
                        ],
                    ],
                ];
            }
            
            public static function query($iTransactionOrClient = null): ModelSharQ
            {
                return new static::$M1ModelSharQClass(static::class);
            }
        };

        $M2 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $m1Id;

            public static function getTableName(): string
            {
                return 'M2';
            }

            public static $M3class;
            public static $M2ModelSharQClass;

            protected static array $metadataCache =
            [
                'M2' => [
                    ['COLUMN_NAME' => 'id'],
                ]
            ];
            
            public static function getRelationMappings(): array
            {
                return [
                    'm3' => [
                        'relation'   => static::BELONGS_TO_ONE_RELATION,
                        'modelClass' => static::$M3class,
                        'join'       => [
                            'from' => 'M2.id',
                            'to'   => 'M3.m2Id'
                        ],
                    ],
                ];
            }
            
            public static function query($iTransactionOrClient = null): ModelSharQ
            {
                return new static::$M2ModelSharQClass(static::class);
            }
        };

        $M3 = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $m2Id;

            public static function getTableName(): string
            {
                return 'M3';
            }
            
            public static $M3ModelSharQClass;

            protected static array $metadataCache =
            [
                'M3' => [
                    ['COLUMN_NAME' => 'id'],
                ]
            ];

            public static function query($iTransactionOrClient = null): ModelSharQ
            {
                return new static::$M3ModelSharQClass(static::class);
            }
        };

        $M1ModelSharQ = new class($M1::class) extends \Sharksmedia\Qarium\ModelSharQ
        {
        };
        $M2ModelSharQ = new class($M2::class) extends \Sharksmedia\Qarium\ModelSharQ
        {
        };
        $M3ModelSharQ = new class($M3::class) extends \Sharksmedia\Qarium\ModelSharQ
        {
        };


        $M1::$M2class           = $M2::class;
        $M1::$M1ModelSharQClass = $M1ModelSharQ::class;

        $M2::$M3class           = $M3::class;
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
            ->modifyGraph('m2', function($builder) use (&$filter1Check, $M2ModelSharQ)
            {
                $filter1Check = $builder instanceof $M2ModelSharQ;
            })
            ->modifyGraph('m2.m3', function($builder) use (&$filter2Check, $M3ModelSharQ)
            {
                $filter2Check = $builder instanceof $M3ModelSharQ;
            })
            ->run();

        $executedQueries = [
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['M1']],
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['M2']],
            ['sql' => 'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ?', 'bindings' => ['M3']],

            ['sql' => 'SELECT `M1`.`id` AS `id`, `m2`.`id` AS `m2:id`, `m2:m3`.`id` AS `m2:m3:id` FROM `M1` LEFT JOIN `M2` AS `m2` ON(`m2`.`m1Id` = `M1`.`id`) LEFT JOIN `M3` AS `m2:m3` ON(`m2:m3`.`m2Id` = `M2`.`id`)', 'bindings' => []],
        ];

        $this->assertEquals($executedQueries, $this->executedQueries);
        $this->assertTrue($filter1Check);
        $this->assertTrue($filter2Check);
    }


    public function testShouldUseModelSharQInBuilderMethods(): void
    {
        $this->markTestSkipped('Not implemented yet');

        return;

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

            public static $ModelSharQ;
            public static $CustomSharQClass;

            public static function query($iTransactionOrClient = null): ModelSharQ
            {
                if (static::$ModelSharQ === null)
                {
                    static::$ModelSharQ = new static::$CustomSharQClass(static::class);
                }

                $query = static::$ModelSharQ::forClass(static::class)
                    ->transacting($iTransactionOrClient);

                static::onCreateQuery($query);

                return $query;
            }
        };

        $CustomSharQ = new class($TestModel::class) extends ModelSharQ
        {
        };

        $TestModel::$CustomSharQClass = $CustomSharQ::class;

        $checks = [];

        $TestModel::query()
            ->select('*', function($builder) use (&$checks, $CustomSharQ)
            {
                $checks[] = $builder instanceof $CustomSharQ;
            })
            ->where(function($builder) use (&$checks, $CustomSharQ)
            {
                $checks[] = $builder instanceof $CustomSharQ;

                $builder->where(function($builder) use (&$checks, $CustomSharQ)
                {
                    $checks[] = $builder instanceof $CustomSharQ;
                });
            })
            ->modify(function($builder) use (&$checks, $CustomSharQ)
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
}
