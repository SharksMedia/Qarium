<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Tests\Support\TQueryBuilder;

class ContextTest extends Unit
{
    use TQueryBuilder;

    public function testChildQueryOfShouldCopyReferenceOfContext(): void
    {
        $this->markTestSkipped('This test is not working yet.');

        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $builder  = $TestModel::query()->context(['a' => 1]);
        $builder2 = $TestModel::query()->childQueryOf($builder)->context(['b' => 2]);

        $this->assertEquals(['a' => 1, 'b' => 2], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }


    public function testChildQueryOfWithForkOptionShouldCopyContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $builder  = $TestModel::query()->context(['a' => 1]);
        $builder2 = $TestModel::query()->childQueryOf($builder, true)->context(['b' => 2]);

        $this->assertEquals(['a' => 1], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }


    public function testClearContextShouldClearTheContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $builder = $TestModel::query()->context(['a' => 1])->clearContext();
        $this->assertEquals([], $builder->getContext()->userContext->getInternalData());
    }


    public function testContextShouldMergeContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $builder = $TestModel::query()->context(['a' => 1])->context(['b' => 2]);

        global $MockMySQLClient;

        $this->assertEquals(['a' => 1, 'b' => 2], $builder->context()->getInternalData());
        $this->assertTrue($builder->getContext()->transaction === $MockMySQLClient);
    }


    public function testContextWithoutPreviousContext(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
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
            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $builder = $TestModel::query()->context(['a' => 1]);

        $builder2 = clone $builder;
        $builder2->context(['b' => 2]);

        $this->assertEquals(['a' => 1], $builder->context()->getInternalData());
        $this->assertEquals(['a' => 1, 'b' => 2], $builder2->context()->getInternalData());
    }


    public function testValuesSavedToContextInHooksShouldBeAvailableLater(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public $a          = null;
            public static $foo = null;

            public static function getTableName(): string
            {
                return 'TestModel';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

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
}
