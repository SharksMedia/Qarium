<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelJoinBuilder;
use Sharksmedia\Objection\ModelQueryBuilderBase;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Join;

class ObjectionToQueryBuilderConvertingOperation extends ModelQueryBuilderOperation
{
    /**
     * @var array|null
     */
    protected $arguments;
    // protected $isObjectionQueryBuilderBase = true;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);
        $this->arguments = null;
    }

    public function getArguments(ModelQueryBuilderOperationSupport $iBuilder): ?array
    {
        return self::convertArgs($this->name, $iBuilder, $this->arguments);
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->arguments = $arguments;

        return self::shouldBeAdded($this->name, $iBuilder, $arguments);
    }

    private static function shouldBeAdded(string $opName, ModelQueryBuilderOperationSupport $iBuilder, array $arguments)
    {
        // PHP does not have undefined, so this function always returns true...
        return true;
    }

    private function convertArgs(string $opName, ModelQueryBuilderOperationSupport $iBuilder, array $arguments): ?array
    {
        return array_map(function($argument) use($opName, $iBuilder)
        {
            if(self::hasToQueryBuilderRawMethod($argument))
            {
                return self::convertToQueryBuilderRaw($argument, $iBuilder);
            }
            else if(self::isObjectionQueryBuilderBase($argument))
            {
                return self::convertQueryBuilderBase($argument, $iBuilder);
            }
            else if(is_array($argument))
            {
                return $this->convertArray($argument, $iBuilder);
            }
            else if($argument instanceof \Closure)
            {
                return $this->convertFunction($argument, $iBuilder);
            }
            // else if(self::isModel($argument))
            // {
            //     return self::convertModel($argument);
            // }
            else if(self::isObject($argument))
            {
                return self::convertPlainObject($argument, $iBuilder);
            }
            else
            {
                return $argument;
            }
        }, $arguments);
    }

    private static function hasToQueryBuilderRawMethod($item): bool
    {
        return self::isObject($item) && method_exists($item, 'toQueryBuilderRaw');
    }

    private static function convertToQueryBuilderRaw($item, ModelQueryBuilderOperationSupport $iBuilder)
    {
        return $item->toQueryBuilderRaw($iBuilder);
    }

    private static function isObjectionQueryBuilderBase($item): bool
    {
        return $item instanceof ModelQueryBuilderBase;
        // return self::isObject($item) && $item->isObjectionQueryBuilderBase === true;
    }

    private static function isQueryBuilder($item): bool
    {
        return $item instanceof QueryBuilder;
    }

    private static function isQueryBuilderJoinBuilder($item): bool
    {
        return $item instanceof \Sharksmedia\QueryBuilder\Statement\Join;
    }

    private static function convertQueryBuilderBase($item, ModelQueryBuilderOperationSupport $iBuilder)
    {
        // FIXME:: Implement me !!!
        throw new \Exception('Not implemented yet');
    }

    private function convertArray(array $arr, ModelQueryBuilderOperationSupport $iBuilder): array
    {
        return array_map(function($item) use($iBuilder)
        {
            if(self::hasToQueryBuilderRawMethod($item))
            {
                return self::convertToQueryBuilderRaw($item, $iBuilder);
            }
            else if(self::isObjectionQueryBuilderBase($item))
            {
                return self::convertQueryBuilderBase($item, $iBuilder);
            }
            else
            {
                return $item;
            }
        }, $arr);
    }

    private function convertFunction($func, ModelQueryBuilderOperationSupport $iBuilder)
    {
        return function(...$args) use($func, $iBuilder)
        {
            $item = $args[0] ?? null;

            if(self::isQueryBuilder($item))
            {
                return self::convertQueryBuilderFunction($item, $func, $iBuilder);
            }
            else if(self::isQueryBuilderJoinBuilder($item))
            {
                return self::convertJoinBuilderFunction($item, $func, $iBuilder);
            }
            else
            {
                return $func(...$args);
            }
        };
    }

    private static function convertQueryBuilderFunction(QueryBuilder $iQueryBuilder, $func, ModelQueryBuilderOperationSupport $iBuilder)
    {
        $convertedQueryBuilder = ModelQueryBuilderOperationSupport::forClass($iBuilder->getModelClass());

        $convertedQueryBuilder->setIsPartial(true)->subQueryOf($iBuilder);
        $func($convertedQueryBuilder);

        $convertedQueryBuilder->toQueryBuilder($iQueryBuilder);
    }

    private static function convertJoinBuilderFunction(Join $iQueryBuilderJoin, \Closure $func, ModelQueryBuilderOperationSupport $iBuilder)
    {
        $iJoinClauseBuilder = \Sharksmedia\Objection\JoinBuilder::forClass($iBuilder->getModelClass());
        
        $iJoinClauseBuilder->setIsPartial(true)->subQueryOf($iBuilder);
        $func($iJoinClauseBuilder);

        $iJoinClauseBuilder->toQueryBuilder($iQueryBuilderJoin);
    }

    private static function isModel($item): bool
    {
        return $item instanceof Model;
    }

    private static function convertPlainObject($obj, ModelQueryBuilder $iBuilder)
    {
        return array_reduce(array_keys($obj), function($out, $key) use($obj, $iBuilder)
        {
            $item = $obj[$key];

            if($item === null)
            {
                return $out;
            }
            else if(self::hasToQueryBuilderRawMethod($item))
            {
                $out[$key] = self::convertToQueryBuilderRaw($item, $iBuilder);
            }
            else if($this->_isObjectionQueryBuilderBase($item))
            {
                $out[$key] = self::convertQueryBuilderBase($item, $iBuilder);
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
        return is_object($item) && !self::isFunction($item);
    }
}
