<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

class ModelSharQContextBase
{
    /**
     * @var ModelSharQContextUser|null
     */
    public ?ModelSharQContextUser $userContext = null;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @return SharQ|null
     */
    public $iSharQ = null;

    /**
     * @return array|null
     */
    public $aliasMap = null;

    /**
     * @return array|null
     */
    public $tableMap = null;

    private array $internalData = [];

    public function __get($name)
    {
        return $this->internalData[$name];
    }

    public function __set($name, $value)
    {
        $this->internalData[$name] = $value;
    }

    public function getInternalData(): array
    {
        return $this->internalData;
    }

    public function __construct(?ModelSharQOperationSupport $iBuilder=null)
    {
        if($iBuilder === null) return;

        $userContextClass = $iBuilder->getModelSharQUserContextClass();

        if($iBuilder !== null) $this->userContext = new $userContextClass($iBuilder);

        $this->options = $iBuilder->getInternalOptions();
    }

    public function getRunBeforeCallback(): callable
    {
        return function(){};
    }

    public function getRunAfterCallback(): callable
    {
        return function(){};
    }

    public function getOnBuildCallback(): callable
    {
        return function(){};
    }

    public function __clone(): void
    {
        foreach(get_object_vars($this) as $name=>$value)
        {
            if(is_object($value)) $this->{$name} = clone $value;
        }
    }


}
