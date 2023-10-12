<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\SharQ\SharQ;

class MockQUeryBuilder
{
    private SharQ $iSharQ;
    private \Closure $mockExecutor;
    private $mock;
    private $queryBuilderMethods = []; // Initialize this array with required method names.

    public function __construct(SharQ $iSharQ, \Closure $mockExecutor)
    {
        $this->iSharQ       = $iSharQ;
        $this->mockExecutor = $mockExecutor;

        $this->mock = new \stdClass();

        $this->initMockMethods();
        $this->initMockProperties();
    }

    public function _mock($table): SharQ
    {
        return $this->mock->getSharQ()->table($table);
    }

    private function initMockMethods(): void
    {
        foreach ($this->queryBuilderMethods as $methodName)
        {
            $this->mock[$methodName] = function(...$args) use ($methodName)
            {
                return $this->wrapBuilder(call_user_func_array([$this->iSharQ, $methodName], $args));
            };
        }
    }

    private function initMockProperties()
    {
        $keys = array_unique(array_merge(array_keys(get_object_vars($this->iSharQ)), ['client']));
        
        foreach ($keys as $key)
        {
            $value = $this->iSharQ->$key;

            if (in_array($key, $this->queryBuilderMethods))
            {
                continue;
            }

            if (is_callable($value))
            {
                $this->mock[$key] = function(...$args) use ($value)
                {
                    return call_user_func_array($value, $args);
                };
            }
            else
            {
                $this->mock[$key] = $value;
            }
        }
    }

    private function wrapBuilder($builder)
    {
        $builder->execute = function(...$args) use ($builder)
        {
            return call_user_func($this->mockExecutor, $this->mock, $builder, $args);
        };

        return $builder;
    }

    public function __call($methodName, $arguments)
    {
        if (isset($this->mock[$methodName]) && is_callable($this->mock[$methodName]))
        {
            return call_user_func_array($this->mock[$methodName], $arguments);
        }

        return null;
    }

    public function __get($key)
    {
        return isset($this->mock[$key]) ? $this->mock[$key] : null;
    }

    public function __set($key, $value)
    {
        $this->mock[$key] = $value;
    }
}

