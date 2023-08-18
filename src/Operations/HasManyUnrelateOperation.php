<?php

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class HasManyUnrelateOperation extends UnrelateOperation
{
    public function queryExecutor(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperationSupport
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

        $query->copyFrom($iBuilder, ModelSharQOperationSupport::JOIN_SELECTOR);

        $query->copyFrom($iBuilder, ModelSharQOperationSupport::WHERE_SELECTOR);

        $query->whereInComposite($relatedRefs, $ownerValues);

        $query->modify($this->iRelation->getModify());

        return $query;
        

        return $relatedModelClass::query()
            ->childQueryOf($iBuilder)
            ->patch($patch)
            ->copyFrom($iBuilder, ModelSharQOperationSupport::JOIN_SELECTOR)
            ->copyFrom($iBuilder, ModelSharQOperationSupport::WHERE_SELECTOR)
            ->whereInComposite($relatedRefs, $ownerValues)
            ->modify($this->iRelation->getModify());
    }
}
