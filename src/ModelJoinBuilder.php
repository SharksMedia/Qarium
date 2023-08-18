<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\Operations\SharQOperation;

class ModelJoinBuilder extends ModelSharQOperationSupport
{
    /**
     * @param array $args
     */
    public function using(...$args): self
    {
        return $this->addOperation(new SharQOperation('using'), $args);
    }

    /**
     * @param array $args
     */
    public function on(...$args): self
    {
        return $this->addOperation(new SharQOperation('on'), $args);
    }

    /**
     * @param array $args
     */
    public function orOn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOn'), $args);
    }

    /**
     * @param array $args
     */
    public function onBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('onBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function onIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('onIn'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function onNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNull'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function onExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('onExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnExists'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('onNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function type(...$args): self
    {
        return $this->addOperation(new SharQOperation('type'), $args);
    }

    /**
     * @param array $args
     */
    public function andOn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNull'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnExists'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnJsonPathEquals(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnJsonPathEquals'), $args);
    }

    /**
     * @param array $args
     */
    public function onVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('onVal'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('andOnVal'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnVal(...$args): self
    {
        return $this->addOperation(new SharQOperation('orOnVal'), $args);
    }
}
