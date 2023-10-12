<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\RelationExpression;
use Sharksmedia\Qarium\ModelSharQ;

class EagerOperation extends ModelSharQOperation
{
    /**
     * 2023-07-10
     * @var RelationExpression
     */
    private $iRelationExpression = null;

    /**
     * 2023-07-10
     * @var array
     */
    private array $modifiersAtPath = [];

    /**
     * 2023-07-10
     * @var array|null
     */
    private ?array $graphOptions = null;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->graphOptions        = $this->options['defaultGraphOptions'] ?? null;
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
        return $this->iRelationExpression !== null;
    }

    public function addModifierAtPath(string $path, \Closure $modifier): self
    {
        $this->modifiersAtPath[] = ['path' => $path, 'modifier' => $modifier];

        return $this;
    }

    public function buildFinalExpression(): RelationExpression
    {
        $expression = clone $this->iRelationExpression;

        foreach ($this->modifiersAtPath as $i => $modifier)
        {
            $name = self::getModifierName($i);

            $iPath = RelationExpression::create($modifier['path']);

            foreach ($expression->expressionAtPath($iPath) as $expr)
            {
                // $expr->addModifier($modifier->modifier);
                $expr->addModifier($name);
            }
        }

        return $expression;
    }

    public function buildFinalModifiers(ModelSharQ $iBuilder): array
    {
        $modifiers = $iBuilder->getModifiers();

        foreach ($this->modifiersAtPath as $i => $modifier)
        {
            $name             = self::getModifierName($i);
            $modifiers[$name] = $modifier['modifier'];
        }

        return $modifiers;
    }

    private static function getModifierName($index)
    {
        return "_f{$index}_";
    }
}
