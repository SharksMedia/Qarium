<?php

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class HasManyUnrelateOperation extends UnrelateOperation
{
    public function queryExecutor(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperationSupport
    {
        $patch = [];
        $iRelatedProp = $this->iRelation->getRelatedProp();
        $ownerValues = $this->iOwner->getProperties($this->iRelation);
        $relatedRefs = $iRelatedProp->refs($iBuilder);

        foreach($iRelatedProp->getReferences() as $i=>$_)
        {
            $iRelatedProp->patch($patch, $i, null);
        }

        $relatedModelClass = $this->iRelation->getRelatedModelClass();

        $query = $relatedModelClass::query();

        $query->childQueryOf($iBuilder);

        $query->patch($patch);

        $query->copyFrom($iBuilder, ModelQueryBuilderOperationSupport::JOIN_SELECTOR);

        $query->copyFrom($iBuilder, ModelQueryBuilderOperationSupport::WHERE_SELECTOR);

        $query->whereInComposite($relatedRefs, $ownerValues);

        $query->modify($this->iRelation->getModify());

        return $query;
        

        return $relatedModelClass::query()
            ->childQueryOf($iBuilder)
            ->patch($patch)
            ->copyFrom($iBuilder, ModelQueryBuilderOperationSupport::JOIN_SELECTOR)
            ->copyFrom($iBuilder, ModelQueryBuilderOperationSupport::WHERE_SELECTOR)
            ->whereInComposite($relatedRefs, $ownerValues)
            ->modify($this->iRelation->getModify());
    }
}
