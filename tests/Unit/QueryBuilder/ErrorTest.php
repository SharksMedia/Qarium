<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Tests\Support\TQueryBuilder;

class ErrorTest extends Unit
{
    use TQueryBuilder;


    public function testAnyReturnValueFromOnErrorShouldBeTheResultOfTheQuery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testShouldFailWithInvalidOperatorUsingRef(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator "lol" is not permitted');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        ModelSharQ::forClass($TestModel::class)
            ->where('SomeTable.someColumn', 'lol', self::ref('SomeOtherTable.someOtherColumn'))
            ->toSQL();
    }


    public function testShouldFailWithInvalidOperatorUsingWhereComposite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator "lol" is not permitted');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        ModelSharQ::forClass($TestModel::class)
            ->whereComposite('SomeTable.someColumn', 'lol', 'SomeOtherTable.someOtherColumn')
            ->toSQL();
    }


    public function testShouldNotExecuteQueryIfAnErrorIsThrownFromRunBefore(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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


    public function testThrowingAtAnyPhaseShouldCallTheOnErrorHook(): void
    {
        $called = false;

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
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
                    ],
                ];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Eager expression not allowed: a.b');

        $TestModel->query()->allowGraph('[a, b.c.[d, e]]')->withGraphJoined('a.b')->run();
    }
}
