<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\Operations\SharQOperation;

class JoinBuilder extends ModelSharQOperationSupport
{
    
    /**
     * @param mixed $args
     */
    function using(...$args): self
    {
        return $this->addOperation(new SharQOperation('using'), $args);
    }

    /**
     * @param mixed $args
     */
    function on(...$args): self
    {
        return $this->addOperation(new SharQOperation('on'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('onBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function onIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('onIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function onExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('onExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function type(...$args): self
    {
        return $this->addOperation(new SharQOperation('type'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnJsonPathEquals(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnJsonPathEquals'), $args);
    }

    /**
     * @param mixed $args
     */
    function onVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('onVal'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnVal'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnVal'), $args);
    }
}
