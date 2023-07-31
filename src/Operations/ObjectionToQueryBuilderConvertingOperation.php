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

class ObjectionToQueryBuilderConvertingOperation extends ModelQueryBuilderOperation
{
    /**
     * @var array|null
     */
    protected $arguments;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);
        $this->arguments = null;
    }

    public function getArguments(ModelQueryBuilder $iBuilder): ?array
    {
        return self::convertArgs($this->name, $iBuilder, $this->arguments);
    }

    public function onAdd(ModelQueryBuilder $iBuilder, ...$arguments): bool
    {
        $this->arguments = $arguments;

        return self::shouldBeAdded($this->name, $iBuilder, $arguments);
    }

    private static function shouldBeAdded(string $opName, ModelQueryBuilder $iBuilder, array $arguments)
    {
        // PHP does not have undefined, so this function always returns true...
        return true;
    }

    private static function convertArgs(string $opName, ModelQueryBuilder $iBuilder, array $arguments): ?array
    {
        return array_map(function($argument) use($opName, $iBuilder)
        {
            if(self::hasToKneRawMethod($argument))
            {
                return self::convertToQueryBuilderRaw($argument, $iBuilder);
            }
            else if(self::isObjectionQueryBuilderBase($argument))
            {
                return self::convertQueryBuilderBase($argument, $iBuilder);
            }
            else if(is_array($argument))
            {
                return self::convertArray($argument, $iBuilder);
            }
            else if($argument instanceof \Closure)
            {
                return self::convertFunction($argument, $iBuilder);
            }
            // else if(self::isModel($argument))
            // {
            //     return self::convertModel($argument);
            // }
            else if(is_object($argument))
            {
                return self::convertPlainObject($argument, $iBuilder);
            }
            else
            {
                return $argument;
            }
        }, $arguments);
    }

    private static function hasToKneRawMethod($item): bool
    {
        return is_object($item) && self::isFunction($item->toQueryBuilderRaw); // NOTE: To knex raw might always be a function, becuase it inherits from the base class.
    }

    private static function convertToQueryBuilderRaw($item, ModelQueryBuilder $iBuilder)
    {
        return $item->toQueryBuilderRaw($iBuilder);
    }

    private static function isObjectionQueryBuilderBase($item)
    {
        return is_object($item) && self::isObjectionQueryBuilderBase($item) === true;
    }

    private static function convertQueryBuilderBase($item, ModelQueryBuilder $iBuilder)
    {
        // FIXME:: Implement me !!!
    }

    private static function convertArray(array $arr, ModelQueryBuilder $iBuilder): array
    {
        return array_map(function($item) use($iBuilder)
        {
            if(self::hasToKneRawMethod($item))
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

    private static function convertFunction($func, ModelQueryBuilder $iBuilder)
    {
        return function(...$args) use($func, $iBuilder)
        {
            if(self::isObjectionQueryBuilderBase($this))
            {
                return self::convertQueryBuilderBase($this, $iBuilder);
            }
            else
            {
                return $func(...$args);
            }
        };
    }

    private static function convertQueryBuilderFunction($knexQueryBuilder, $func, ModelQueryBuilder $iBuilder)
    {
        $convertedQueryBuilder = ModelQueryBuilder::forClass($iBuilder->getModelClass());

        $convertedQueryBuilder->setIsPartial(true)->subQueryOf($iBuilder);
        $func($convertedQueryBuilder);

        $convertedQueryBuilder->toQueryBuilderQuery($knexQueryBuilder);
    }

    private static function convertJoinBuilderFunction($knexJoinBuilder, $func, ModelQueryBuilder $iBuilder)
    {
        $iJoinClauseBuilder = ModelJoinBuilder::forClass($iBuilder->getModelClass());
        
        $iJoinClauseBuilder->setIsPartial(true)->subQueryOf($iBuilder);
        $func($iJoinClauseBuilder);

        $iJoinClauseBuilder->toQueryBuilderQuery($knexJoinBuilder);
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
            else if(self::hasToKneRawMethod($item))
            {
                $out[$key] = self::convertToQueryBuilderRaw($item, $iBuilder);
            }
            else if(self::isObjectionQueryBuilderBase($item))
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
}
