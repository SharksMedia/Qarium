<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Qarium\Relations;

use Sharksmedia\Qarium\Operations\HasManyInsertOperation;
use Sharksmedia\Qarium\Operations\HasManyRelateOperation;
use Sharksmedia\Qarium\Operations\HasManyUnrelateOperation;

class HasMany extends Relation
{
    public function insert($_, $iOwner)
    {
        return new HasManyInsertOperation('insert', $this, $iOwner);
    }

    public function relate($_, $iOwner)
    {
        return new HasManyRelateOperation('relate', $this, $iOwner);
    }

    public function unrelate($_, $iOwner)
    {
        return new HasManyUnrelateOperation('unrelate', $this, $iOwner);
    }

    public function hasRelateProperty(Model $iModel): bool
    {
        return $iModel->hasID();
    }

    public function setRelateProperty(Model $iModel, $iValue): void
    {
        $iModel->setID($iValue);
    }

}
