<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Exceptions\ModifierNotFoundError;
use Sharksmedia\Qarium\ModelSharQ;
use Tests\Support\TQueryBuilder;

class ModifierTest extends Unit
{
    use TQueryBuilder;


    public function testModifiersShouldReturnEagerExpressionsModifiersAsObject(): void
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

        $foo = function ($builder)
        {
            return $builder->where('foo');
        };

        $builder = $TestModel::query()->withGraphJoined('[a, b.c(foo)]')->modifiers(['foo' => $foo]);

        $this->assertEquals(['foo' => $foo], $builder->getModifiers());
    }


    public function testModifyAcceptAListOfStringsAndCallTheCorrespondingModifiers(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getModifiers(): array
            {
                $a = function($qb, &$builder, $markACalledFunc, $markBCalledFunc)
                {
                    $called = $qb === $builder;
                    $markACalledFunc($called);
                };
                $b = function($qb, &$builder, $markACalledFunc, $markBCalledFunc)
                {
                    $called = $qb === $builder;
                    $markBCalledFunc($called);
                };

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

        $markACalled = function($called) use (&$aCalled)
        { $aCalled = $called; };
        $markBCalled = function($called) use (&$bCalled)
        { $bCalled = $called; };

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
        $TestModel       = new class extends \Sharksmedia\Qarium\Model
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


    public function testModifyShouldDoNothingWhenReceivingUndefined(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };

        $builder = ModelSharQ::forClass($TestModel::class);
        $res     = null;

        $res = $builder->modify(null);

        $this->assertSame($builder, $res);
    }


    public function testModifyShouldExecuteTheGivenFunctionAndPassTheBuilderToIt(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };

        $builder = ModelSharQ::forClass($TestModel::class);
        $called  = false;

        $builder->modify(function($b) use ($builder, &$called)
        {
            $called = true;
            $this->assertSame($builder, $b);
        });

        $this->assertTrue($called);
    }


    public function testShouldBeAbleToPassArgumentsToModify(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };

        $builder = ModelSharQ::forClass($TestModel::class);
        $called1 = false;
        $called2 = false;

        // Should accept a single function.
        $builder->modify(function($query, $arg1, $arg2) use ($builder, &$called1)
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
                function($query, $arg1, $arg2) use ($builder, &$called1)
                {
                    $called1 = true;
                    $this->assertSame($builder, $query);
                    $this->assertEquals('foo', $arg1);
                    $this->assertEquals(1, $arg2);
                },

                function($query, $arg1, $arg2) use ($builder, &$called2)
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
                    'modifier1' => function($query, $arg1, $arg2, $context, $markCalledFunc, &$builder)
                    {
                        $markCalledFunc();
                        $context->assertSame($builder, $query);
                        $context->assertEquals('foo', $arg1);
                        $context->assertEquals(1, $arg2);
                    },

                    'modifier2' => function($query, $arg1, $arg2, $context, $markCalledFunc, &$builder)
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
        $builder->modify('modifier1', 'foo', 1, $this, function() use (&$called1)
        { $called1 = true; }, $builder);
        $this->assertTrue($called1);

        // Should accept an array of modifiers.
        $builder->modify(['modifier1', 'modifier2'], 'foo', 1, $this, function() use (&$called2)
        { $called2 = true; }, $builder);

        $this->assertTrue($called1);
        $this->assertTrue($called2);
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


    public function testShouldThrowIfAnUnknownModifierIsSpecified(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
        };

        $builder = ModelSharQ::forClass($TestModel::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine modify function from provided value: "unknown".');

        $builder->modify('unknown');
    }
}
