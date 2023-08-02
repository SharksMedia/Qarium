<?php

declare(strict_types=1);

namespace Sharksmedia\Objection\Exceptions;

class ModelNotFoundError extends \Exception
{
    private ?string $tableName = null;

    public function getTableName(): ?string
    {// 2023-08-02
        return $this->tableName;
    }
}

