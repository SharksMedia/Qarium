<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Tests\Support\TQueryBuilder;

class UpdateAndInsertTest extends Unit
{
    use TQueryBuilder;

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
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
            ['sql' => 'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, ?, ?)', 'bindings' => [10, 'test', 'beforeInsert']],
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
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
            ['sql' => 'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, ?, ?)', 'bindings' => [10, 'test', 'beforeInsert']],
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
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
            ['sql' => 'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings' => [10, 'test', 'beforeUpdate']],
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
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
            ['sql' => 'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings' => [10, 'test', 'beforeUpdate']],
        ], $this->executedQueries);
    }


    public function testUpdateShouldCallBeforeUpdateOnTheModel(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
                // ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = DATABASE()', 'bindings'=>['Model']],
                ['sql' => 'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings' => [10, 'test', 'beforeUpdate']],
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
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
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
            // ['sql'=>'SELECT * FROM `information_schema`.`columns` WHERE `table_name` = ? AND `table_catalog` = DATABASE()', 'bindings'=>['Model']],
            ['sql' => 'UPDATE `Model` SET `a` = ?, `b` = ?, `c` = ?', 'bindings' => ['10', 'test', 'beforeUpdate']],
        ], $this->executedQueries);
    }

    public function testInsertUpdate(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }

            public static function getTableIDs(): array
            {
                return ['a'];
            }
        };

        $modelData = [
            'a' => 1,
            'b' => null,
            'c' => 'John Doe',
        ];

        $query = $TestModel::query()
            ->insert($modelData)
            ->onConflict('personID')
            ->merge([
                'a',
                'b',
                'c'
            ]);

        $query->run();

        $this->assertEquals([
            ['sql' => 'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, NULL, ?) ON DUPLICATE KEY UPDATE `a` = VALUES(`a`), `b` = VALUES(`b`), `c` = VALUES(`c`)', 'bindings' => [1, 'John Doe']],
        ], $this->executedQueries);
    }

    public function testInsertUpdateValues(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a;
            public $b;
            public $c;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a' ],
                    [ 'COLUMN_NAME' => 'b' ],
                    [ 'COLUMN_NAME' => 'c' ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }

            public static function getTableIDs(): array
            {
                return ['a'];
            }
        };

        $modelData = [
            'a' => 1,
            'b' => null,
            'c' => 'John Doe',
        ];

        $query = $TestModel::query()
            ->insert($modelData)
            ->onConflict('personID')
            ->merge([
                'a' => 1,
                'b' => null,
                'c' => 'John Doe The Second',
            ]);

        $query->run();

        $this->assertEquals([
            ['sql' => 'INSERT INTO `Model` (`a`, `b`, `c`) VALUES (?, NULL, ?) ON DUPLICATE KEY UPDATE `a` = ?, `b` = NULL, `c` = ?', 'bindings' => [1, 'John Doe', 1, 'John Doe The Second']],
        ], $this->executedQueries);
    }
}
