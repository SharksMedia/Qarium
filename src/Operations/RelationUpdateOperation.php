<?php

declare(strict_types=1);

// 2023-08-17

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\Relations\Relation;
use Sharksmedia\Qarium\Relations\RelationOwner;

class RelationUpdateOperation extends UpdateOperation
{
    protected Relation $iRelation;
    protected RelationOwner $iOwner;

    public function __construct($name, $options=[])
    {
        parent::__construct($name, $options);

        $this->iRelation = $this->options['iRelation'] ?? $this->options['relation'];
        $this->iOwner = $this->options['iOwner'];
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        parent::onBuild($iBuilder);

        $this->iRelation->findQuery($iBuilder, $this->iOwner);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return new RelationFindOperation('find', [
            'iRelation' => $this->iRelation,
            'iOwner' => $this->iOwner,
        ]);
    }

}
