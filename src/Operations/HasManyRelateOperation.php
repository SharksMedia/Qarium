<?php

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\Relations\Relation;
use Sharksmedia\Qarium\Relations\RelationOwner;
use Sharksmedia\Qarium\Utilities;
use Sharksmedia\Qarium\Model;

class HasManyRelateOperation extends RelateOperation
{
    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->input = $arguments[0];

        /** @var \Model $relatedModelClass */
        $relatedModelClass = $this->iRelation->getRelatedModelClass();

        $this->ids = Utilities::normalizeIds($this->input, $relatedModelClass::getIdRelationProperty(), [
            'arrayOutput' => true,
        ]);
        
        self::assertOwnerIsSingleItem($this->iOwner, $this->iRelation);

        return true;
    }

    public function queryExecutor(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperationSupport
    {
        $patch        = [];
        $iRelatedProp = $this->iRelation->getRelatedProp();
        $iOwnerValues = $this->iOwner->getSplitProps($iBuilder, $this->iRelation);

        foreach ($iRelatedProp as $i => $_)
        {
            $iRelatedProp->patch($patch, $i, $iOwnerValues[0][$i]);
        }

        /** @var \Model $relatedModelClass */
        $relatedModelClass = $this->iRelation->getRelatedModelClass();

        return $relatedModelClass::query()
            ->childQueryOf($iBuilder)
            ->patch($patch)
            ->copyFrom($iBuilder, ModelSharQOperationSupport::JOIN_SELECTOR)
            ->copyFrom($iBuilder, ModelSharQOperationSupport::WHERE_SELECTOR)
            ->findByIds($this->ids)
            ->modify($this->iRelation->getModify());
    }

    private static function assertOwnerIsSingleItem(RelationOwner $iOwner, Relation $iRelation): void
    {
        $isSingleModel = $iOwner->getType() === RelationOwner::TYPE_MODELS && count($iOwner->getValue()) === 1;

        $normalizedIDs = Utilities::normalizeIds($iOwner->getValue(), $iRelation->getOwnerProp(), [
            'arrayOutput' => true,
        ]);

        $isSingleID = $iOwner->getType() === RelationOwner::TYPE_IDENTIFIERS && count($normalizedIDs) ===  1;

        $isSharQ = $iOwner->getType() === RelationOwner::TYPE_QUERY_BUILDER;

        if (!$isSingleModel && !$isSingleID && !$isSharQ)
        {
            throw new \Exception(
                'Can only relate items for one parent at a time in case of HasManyRelation. '.
                'Otherwise multiple update queries would need to be created. '.
                'If you need to relate items for multiple parents, simply loop through them. '.
                'That\'s the most performant way.'
            );
        }
    }
}
