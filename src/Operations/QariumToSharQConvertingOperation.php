<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ColumnRef;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelJoinBuilder;
use Sharksmedia\Qarium\ModelSharQBase;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\ReferenceBuilder;
use Sharksmedia\Qarium\Transformations\CompositeQueryTransformation;
use Sharksmedia\Qarium\Transformations\WrapMysqlModifySubqueryTransformation;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Join;

class QariumToSharQConvertingOperation extends ModelSharQOperation
{
    /**
     * @var array|null
     */
    protected $arguments;
    // protected $isModelSharQBase = true;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);
        $this->arguments = null;
    }

    public function getArgumentsRaw(): ?array
    {
        return $this->arguments;
    }

    public function getArguments(ModelSharQOperationSupport $iBuilder): ?array
    {
        return self::convertArgs($this->name, $iBuilder, $this->arguments);
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->arguments = $arguments;

        return self::shouldBeAdded($this->name, $iBuilder, $arguments);
    }

    private static function shouldBeAdded(string $opName, ModelSharQOperationSupport $iBuilder, array $arguments)
    {
        // PHP does not have undefined, so this function always returns true...
        return true;
    }

    private function convertArgs(string $opName, ModelSharQOperationSupport $iBuilder, array $arguments): ?array
    {
        return array_map(function($argument) use ($opName, $iBuilder)
        {
            if (self::hasToSharQRawMethod($argument))
            {
                return self::convertToSharQRaw($argument, $iBuilder);
            }
            else if (self::isModelSharQBase($argument))
            {
                return self::convertSharQBase($argument, $iBuilder);
            }
            else if (is_array($argument))
            {
                return $this->convertArray($argument, $iBuilder);
            }
            else if ($argument instanceof \Closure)
            {
                return $this->convertFunction($argument, $iBuilder);
            }
            // else if(self::isModel($argument))
            // {
            //     return self::convertModel($argument);
            // }
            else if (self::isObject($argument))
            {
                return self::convertPlainObject($argument, $iBuilder);
            }
            else
            {
                return $argument;
            }
        }, $arguments);
    }

    private static function hasToSharQRawMethod($item): bool
    {
        return self::isObject($item) && method_exists($item, 'toSharQRaw');
    }

    /**
     * @param ReferenceBuilder $item
     * @param ModelSharQOperationSupport $iBuilder
     * @return \Sharksmedia\SharQ\Statement\Raw
     */
    private static function convertToSharQRaw($item, ModelSharQOperationSupport $iBuilder): \Sharksmedia\SharQ\Statement\Raw
    {
        return $item->toSharQRaw($iBuilder);
    }

    private static function isModelSharQBase($item): bool
    {
        return $item instanceof ModelSharQBase;
        // return self::isObject($item) && $item->isModelSharQBase === true;
    }

    private static function isSharQ($item): bool
    {
        return $item instanceof SharQ;
    }

    private static function isSharQJoinBuilder($item): bool
    {
        return $item instanceof \Sharksmedia\SharQ\Statement\Join;
    }

    private static function convertSharQBase(ModelSharQOperationSupport $iQuery, ModelSharQOperationSupport $iBuilder): SharQ
    {
        $iTransformation = new CompositeQueryTransformation([new WrapMysqlModifySubqueryTransformation()]);
        
        $iQuery = $iTransformation->onConvertSharQBase($iQuery, $iBuilder);

        return $iQuery->subQueryOf($iBuilder)->toSharQ();
    }

    private function convertArray(array $arr, ModelSharQOperationSupport $iBuilder): array
    {
        return array_map(function($item) use ($iBuilder)
        {
            if (self::hasToSharQRawMethod($item))
            {
                return self::convertToSharQRaw($item, $iBuilder);
            }
            else if (self::isModelSharQBase($item))
            {
                return self::convertSharQBase($item, $iBuilder);
            }
            else
            {
                return $item;
            }
        }, $arr);
    }

    private function convertFunction($func, ModelSharQOperationSupport $iBuilder)
    {
        return function(...$args) use ($func, $iBuilder)
        {
            $item = $args[0] ?? null;

            if (self::isSharQ($item))
            {
                return self::convertSharQFunction($item, $func, $iBuilder);
            }
            else if (self::isSharQJoinBuilder($item))
            {
                return self::convertJoinBuilderFunction($item, $func, $iBuilder);
            }
            else
            {
                return $func(...$args);
            }
        };
    }

    private static function convertSharQFunction(SharQ $iSharQ, $func, ModelSharQOperationSupport $iBuilder)
    {
        $convertedSharQ = ModelSharQ::forClass($iBuilder->getModelClass());

        $convertedSharQ->setIsPartial(true)->subQueryOf($iBuilder);
        $func($convertedSharQ);

        $convertedSharQ->toSharQ($iSharQ);
    }

    private static function convertJoinBuilderFunction(Join $iSharQJoin, \Closure $func, ModelSharQOperationSupport $iBuilder)
    {
        $iJoinClauseBuilder = \Sharksmedia\Qarium\JoinBuilder::forClass($iBuilder->getModelClass());
        
        $iJoinClauseBuilder->setIsPartial(true)->subQueryOf($iBuilder);
        $func($iJoinClauseBuilder);

        $iJoinClauseBuilder->toSharQ($iSharQJoin);
    }

    private static function isModel($item): bool
    {
        return $item instanceof Model;
    }

    private static function convertPlainObject($obj, ModelSharQ $iBuilder)
    {
        return array_reduce(array_keys($obj), function($out, $key) use ($obj, $iBuilder)
        {
            $item = $obj[$key];

            if ($item === null)
            {
                return $out;
            }
            else if (self::hasToSharQRawMethod($item))
            {
                $out[$key] = self::convertToSharQRaw($item, $iBuilder);
            }
            else if ($this->_isModelSharQBase($item))
            {
                $out[$key] = self::convertSharQBase($item, $iBuilder);
            }
            else
            {
                $out[$key] = $item;
            }

            return $out;
        }, []);
    }

    private static function isFunction(&$item): bool
    {// 2023-08-01
        return $item instanceof \Closure;
    }

    private static function isObject(&$item): bool
    {
        return is_object($item) && !self::isFunction($item) && !($item instanceof ColumnRef);
    }
}
