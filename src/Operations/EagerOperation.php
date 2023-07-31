<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\RelationExpression;
use Sharksmedia\Objection\ModelQueryBuilder;

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
        $this->iRelationExpression = RelationExpression::create();
    }

    public function getExpression(): RelationExpression
    {
        return $this->iRelationExpression;
    }

    public function setExpression(RelationExpression $relationExpression): self
    {
        $this->iRelationExpression = $relationExpression;

        return $this;
    }
    
    public function getGraphOptions(): array
    {
        return $this->graphOptions ?? [];
    }

    public function setGraphOptions(array $graphOptions): self
    {
        $this->graphOptions = $graphOptions;

        return $this;
    }

    public function hasExpression(): bool
    {
        return false;
    }

    public function addModifierAtPath(string $path, \Closure $modifier): self
    {
        $this->modifiersAsPath[] = ['path'=>$path, 'modifier'=>$modifier];

        return $this;
    }

    public function buildFinalExpression(): RelationExpression
    {
        $expression = clone $this->iRelationExpression;

        foreach($this->modifiersAsPath as $name=>$modifier)
        {
            foreach($expression->expressionsAtPath($modifier->path) as $expr)
            {
                $expr->addModifier($modifier->modifier);
            }
        }

        return $expression;
    }

    public function buildFinalModifiers(ModelQueryBuilder $iBuilder): array
    {
        $modifiers = $iBuilder->getModifiers();

        foreach($this->modifiersAsPath as $name=>$modifier)
        {
            $modifiers[$name] = $modifier->modifier;
        }

        return $modifiers;
    }

}
