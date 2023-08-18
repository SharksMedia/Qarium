<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-10

class RelationDoesNotExistError extends \Exception
{
    /**
     * @var class-string<\Model>
     */
    private string $modelClass;

    private string $relationName;

    public function __construct(string $modelClass, string $relationName, string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->modelClass = $modelClass;
        $this->relationName = $relationName;

        $message = 'Relation "'.$relationName.'" does not exist on model "'.$modelClass.'"';
        parent::__construct($message, $code, $previous);
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}

