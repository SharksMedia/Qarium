<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-10

class RelationExpressionError extends \Exception
{
    /**
     * @var RelationExpression|null
     */
    private ?RelationExpression $iRelationExpression;

    public function __construct(RelationExpression $iRelationExpression, string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->iRelationExpression = $iRelationExpression;

        $message = 'Invalid relation expression: '.$message;
        parent::__construct($message, $code, $previous);
    }

    public function getRelationExpression(): ?RelationExpression
    {
        return $this->iRelationExpression;
    }
}

