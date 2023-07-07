<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\QueryBuilderOperation;

class ModelJoinBuilder extends ModelQueryBuilderOperationSupport
{

    /**
     * @param array $args
     */
    public function using(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('using'), $args);
    }

    /**
     * @param array $args
     */
    public function on(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('on'), $args);
    }

    /**
     * @param array $args
     */
    public function orOn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOn'), $args);
    }

    /**
     * @param array $args
     */
    public function onBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function onIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onIn'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function onNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNull'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function onExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnExists'), $args);
    }

    /**
     * @param array $args
     */
    public function onNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function type(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('type'), $args);
    }

    /**
     * @param array $args
     */
    public function andOn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNull'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnExists'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnJsonPathEquals(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnJsonPathEquals'), $args);
    }

    /**
     * @param array $args
     */
    public function onVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onVal'), $args);
    }

    /**
     * @param array $args
     */
    public function andOnVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnVal'), $args);
    }

    /**
     * @param array $args
     */
    public function orOnVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnVal'), $args);
    }
}
