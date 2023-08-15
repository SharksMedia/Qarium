<?php

declare(strict_types=1);

// 2023-08-14

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\Objection\Relations\BelongsToOne;

class RelationFindOperation extends FindOperation
{
    private $iRelation;
    private $iOwner;
    private bool $alwaysReturnArray;
    private bool $assignResultToOwner;
    private $iRelationProperty;
    private $omitProps;
    private $alias;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->iRelation = $options['relation'];
        $this->iOwner = $options['iOwner'];
        $this->alwaysReturnArray = false;
        $this->assignResultToOwner = false;
        $this->iRelationProperty = $options['iRelationProperty'] ?? $this->iRelation->getName();
        $this->omitProps = $options['omitProps'] ?? [];
        $this->alias = null;
    }

    public function setAlwaysReturnArray(bool $alwaysReturnArray): self
    {
        $this->alwaysReturnArray = $alwaysReturnArray;
        return $this;
    }

    public function setAssignResultToOwner(bool $assignResultToOwner): self
    {
        $this->assignResultToOwner = $assignResultToOwner;
        return $this;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function onBuild(ModelQueryBuilderOperationSupport $iBuilder): void
    {
        $this->maybeApplyAlias($iBuilder, $this->iOwner);
        $this->iRelation->findQuery($iBuilder, $this->iOwner);

        if($this->assignResultToOwner && $this->iOwner->isModels())
        {
            $this->selectMissingJoinColumns($iBuilder);
        }
    }

    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$related)
    {
        $isOneToOne = $this->iRelation instanceof BelongsToOne;

        if($this->assignResultToOwner && $this->iOwner->isModels())
        {
            $iOwners = $this->iOwner->getModels();
            $relationByOwnerId = [];

            foreach($related as $rel)
            {
                $key = $this->iRelation->getRelatedProp()->getPropKey($rel);
                $relationByOwnerId[$key] = $relationByOwnerId[$key] ?? [];

                $relationByOwnerId[$key][] = $rel;
            }

            foreach($iOwners as $iOwner)
            {
                $key = $this->iRelation->getOwnerProp()->getPropKey($iOwner);
                $related = $relationByOwnerId[$key] ?? null;

                $iOwner->$this->iRelationProperty = $isOneToOne
                   ? $related[0] ?? null
                    : $related ?? [];
            }
        }

        return $related;
    }

    public function onAfter3(ModelQueryBuilderOperationSupport $iBuilder, &$related)
    {
        $isOneToOne = $this->iRelation instanceof BelongsToOne;
        $internalOptions = $iBuilder->getInternalOptions();

        if(!($internalOptions['keepImplicitJoinProps'] ?? false))
        {
            $this->omitImplicitJoinProps($related);
        }

        if(!$this->alwaysReturnArray && $isOneToOne && count($related) === 0)
        {
            $related = $related[0] ?? null;
        }

        return parent::onAfter3($iBuilder, $related);
    }

    private function selectMissingJoinColumns(ModelQueryBuilder $iBuilder)
    {
        $iRelatedProp = $this->iRelation->getRelatedProp();
        $addedSelects = [];

        for($c=0, $lc = $iRelatedProp->getSize(); $c < $lc; ++$c)
        {
            $fullColumn = $iRelatedProp->ref($iBuilder, $c)->getFullColumn($iBuilder);
            $prop = $iRelatedProp->getProperties()[$c];
            $col = $iRelatedProp->getColumns()[$c];

            if(!$iBuilder->hasSelectionAs($fullColumn, $col) && !in_array($fullColumn, $addedSelects))
            {
                $this->omitProps[] = $prop;
                $addedSelects[] = $fullColumn;
            }
        }

        if(count($addedSelects) > 0)
        {
            $iBuilder->select($addedSelects);
        }
    }

    private function maybeApplyAlias(ModelQueryBuilder $iBuilder): void
    {
        if($iBuilder->getAlias() === null && $this->alias !== null)
        {
            $iBuilder->setAlias($this->alias);
        }
    }

    private function omitImplicitJoinProps($related)
    {
        $relatedModelClass = $this->iRelation->getRelatedModelClass();

        if(count($this->omitProps) === 0 || !$related) return $related;

        if(!is_array($related))
        {
            return $this->omitImplicitJoinPropsFromOne($relatedModelClass, $related);
        }

        if(count($related) === 0) return $related;

        foreach($related as $rel)
        {
            $this->omitImplicitJoinPropsFromOne($relatedModelClass, $rel);
        }

        return $related;
    }

    /**
     * @param \Model|class-string<\Model> $relatedModelClass
     * @param $model
     * @return mixed
     */
    private function omitImplicitJoinPropsFromOne($relatedModelClass, $model)
    {
        for($c=0, $lc=count($this->omitProps); $c < $lc; ++$c)
        {
            $relatedModelClass::omitImpl($model, $this->omitProps[$c]);
        }

        return $model;
    }
}
