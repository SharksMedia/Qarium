<?php

declare(strict_types=1);

namespace Sharksmedia\Objection\Exceptions;

class ModifierNotFoundError extends \Exception
{
    private string $modifierName;

    public function __construct(?string $modifierName=null, $code=0, \Throwable $previous=null)
    {
        parent::__construct('Unable to determine modify function from provided value: "'.($modifierName ?? 'unknown').'".', $code, $previous);

        $this->modifierName = $modifierName;
    }

    public function getModifierName(): string
    {
        return $this->modifierName;
    }
}
