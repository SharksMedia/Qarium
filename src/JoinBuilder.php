<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\QueryBuilderOperation;

class JoinBuilder extends ModelQueryBuilderOperationSupport
{
    
    /**
     * @param mixed $args
     */
    function using(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('using'), $args);
    }

    /**
     * @param mixed $args
     */
    function on(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('on'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function onIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function onExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function onNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function type(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('type'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotIn'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotNull'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotExists'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnNotBetween'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnJsonPathEquals(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnJsonPathEquals'), $args);
    }

    /**
     * @param mixed $args
     */
    function onVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onVal'), $args);
    }

    /**
     * @param mixed $args
     */
    function andOnVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andOnVal'), $args);
    }

    /**
     * @param mixed $args
     */
    function orOnVal(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orOnVal'), $args);
    }
}
