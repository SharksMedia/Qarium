<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\RelationExpression;

class EagerOperation extends ModelQueryBuilderOperation
{
    /**
     * 2023-07-10
     * @var RelationExpression
     */
    private $iRelationExpression;

    /**
     * 2023-07-10
     * @var array
     */
    private array $modifiersAsPath = [];

    /**
     * 2023-07-10
     * @var array|null
     */
    private ?array $graphOptions = null;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->graphOptions = $this->options['defaultGraphOptions'] ?? null;
    }

    public function getExpression(): RelationExpression
    {
        return $this->iRelationExpression;
    }

    public function setExpression(RelationExpression $relationExpression): void
    {
        $this->iRelationExpression = $relationExpression;
    }
    
    public function getGraphOptions(): ?array
    {
        return $this->graphOptions;
    }

    public function setGraphOptions(array $graphOptions): void
    {
        $this->graphOptions = $graphOptions;
    }

    public function hasExpression(): bool
    {
        return false;
    }

    public function addModifierAtPath(string $path, \Closure $modifier): void
    {
        $this->modifiersAsPath[] = ['path'=>$path, 'modifier'=>$modifier];
    }
}
