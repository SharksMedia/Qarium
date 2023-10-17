<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Tests\Support\TQueryBuilder;

class JoinAndWhereTest extends Unit
{
    use TQueryBuilder;

    public function testOperatorShouldDefaultToEqualWhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereComposite(['A.a', 'B.b'], [1, 2])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a` = ? AND `B`.`b` = ?)',
            $query
        );
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'm2id']
                ],
                'M2' =>
                [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'm1Id']
                ],
                'Model' =>
                [
                    ['COLUMN_NAME' => 'id'],
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
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'm2Id']
                ],
                'M2' =>
                [
                    ['COLUMN_NAME' => 'id'],
                    ['COLUMN_NAME' => 'm1Id']
                ],
                'Model' =>
                [
                    ['COLUMN_NAME' => 'id'],
                ]
            ];
            
            public static function getTableName(): string
            {
                return 'M2';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'm1' => [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => static::$M1class,
                        'join'       => [
                            'from' => 'M2.id',
                            'to'   => 'M1.m2Id',
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


    public function testOrWhereShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testShouldCreateAWhereClauseUsingColumnReferencesInsteadOfValues1(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->where('SomeTable.someColumn', '>', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `SomeTable`.`someColumn` > `SomeOtherTable`.`someOtherColumn`',
            $query
        );
    }


    public function testShouldCreateMultipleWhereQueries(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereComposite(['A.a', 'B.b'], '>', [1, 2])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a` > ? AND `B`.`b` > ?)',
            $query
        );
    }


    public function testShouldWorkLikeANormalWhereWhenOneColumnIsGiven1WhereComposite(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereComposite('A.a', 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` = ?',
            $query
        );
    }


    public function testShouldWorkJustLikeANormalWhereInQueryIfOneColumnIsGiven1(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
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
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite('A.a', 1)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE `A`.`a` IN(?)',
            $query
        );
    }
}
