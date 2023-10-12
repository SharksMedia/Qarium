<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-10

class ModelSharQContext extends ModelSharQContextBase
{
    /**
     * 2023-07-10
     * @var array
     */
    private array $runBefore = [];

    /**
     * 2023-07-10
     * @var array
     */
    private array $runAfter = [];

    /**
     * 2023-07-10
     * @var array
     */
    private array $onBuild = [];

    public $transaction;

    public function addOnBuildCallback(\Closure $callback)
    {
        $this->onBuild[] = $callback;
    }

    public function getRunBeforeCallback(): callable
    {
        return function(ModelSharQ $iBuilder)
        {
            foreach ($this->runBefore as $callback)
            {
                $callback($iBuilder);
            }
        };
    }

    public function getRunAfterCallback(): callable
    {
        return function(ModelSharQ $iBuilder)
        {
            foreach ($this->runAfter as $callback)
            {
                $callback($iBuilder);
            }
        };
    }

    public function getOnBuildCallback(): callable
    {
        return function(ModelSharQ $iBuilder)
        {
            foreach ($this->onBuild as $callback)
            {
                $callback($iBuilder);
            }
        };
    }
}
