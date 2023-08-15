<?php

declare(strict_types=1);

// 2023-08-10

namespace Sharksmedia\Objection\Relations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ReferenceBuilder;
use Sharksmedia\Objection\Utilities;

/**
 * The owner of a relation is the table that the relation is defined on.
 * The owner can be a model, a reference builder, a model query builder
 * or an array of any of the above.
 * this class could be split into 4 classes, but it's not worth it.
 */
class RelationOwner
{
    public const TYPE_MODELS = 'MODELS';
    public const TYPE_REFERENCE = 'REFERENCE';
    public const TYPE_QUERY_BUILDER = 'QUERY_BUILDER';
    public const TYPE_IDENTIFIERS = 'IDENTIFIERS';

    /** @var Model|Model[]|ReferenceBuilder|ReferenceBuilder[]|ModelQueryBuilder $iOwner */
    private $iOwner;

    /** @var string $type */
    private $type;

    /**
     * @param Model|ReferenceBuilder|ModelQueryBuilder $iOwner
     */
    public function __construct($iOwner=null)
    {
        $this->iOwner = $iOwner;
        $this->type = self::detectType($iOwner);
    }

    public static function create($iOwner=null): self
    {
        return new self($iOwner);
    }

    public function getValue()
    {
        return $this->iOwner;
    }

    public static function createParentReference(ModelQueryBuilder $iBuilder, Relation $iRelation): self
    {
        $iOwnerProperty = $iRelation->getOwnerProp();
        $iPartialQuery = self::findFirstNonPartialAncestorQuery($iBuilder);

        return self::create($iOwnerProperty->createReferences($iPartialQuery));
    }

    private static function detectType($owner): string
    {
        if(self::isModel($owner) || self::isModelArray($owner))
        {
            return self::TYPE_MODELS;
        }
        else if(self::isReferenceArray($owner))
        {
            return self::TYPE_REFERENCE;
        }
        else if(self::isModelQueryBuilder($owner))
        {
            return self::TYPE_QUERY_BUILDER;
        }

        return self::TYPE_IDENTIFIERS;
    }

    private static function isModel($value): bool
    {
        return $value instanceof Model;
    }

    private static function isModelArray($value): bool
    {
        return is_array($value) && self::isModel($value[0] ?? null);
    }

    private static function isReference($value): bool
    {
        return $value instanceof ReferenceBuilder;
    }

    private static function isReferenceArray($value): bool
    {
        return is_array($value) && self::isReference($value[0] ?? null);
    }

    private static function isModelQueryBuilder($value): bool
    {
        return $value instanceof ModelQueryBuilder;
    }

    private static function findFirstNonPartialAncestorQuery(ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        $iParentBuilder = $iBuilder->getParentQuery();

        while($iParentBuilder->getIsPartial())
        {
            if($iParentBuilder->getParentQuery() === null) break;

            $iParentBuilder = $iParentBuilder->getParentQuery();
        }

        return $iParentBuilder;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param ModelQueryBuilder $iBuilder
     * @param Relation $iRelation
     * @param array<int, ReferenceBuilder> $iRelatedReferences
     * @return ModelQueryBuilder
     */
    public function buildFindQuery(ModelQueryBuilder $iBuilder, Relation $iRelation, array $iRelatedReferences)
    {
        if($this->getType() === self::TYPE_REFERENCE)
        {
            foreach($iRelatedReferences as $i=>$iReferenceBuilder)
            {
                $iBuilder->where($iReferenceBuilder, $this->iOwner[$i]);
            }
        }
        else if(in_array($this->getType(), [self::TYPE_MODELS, self::TYPE_IDENTIFIERS, self::TYPE_QUERY_BUILDER]))
        {
            $values = $this->getProperties($iRelation);

            if($values)
                $iBuilder->whereInComposite($iRelatedReferences, $values);
            else
                $iBuilder->where(false)->resolve([]);
        }
        else
        {
            $iBuilder->where(false)->resolve([]);
        }

        return $iBuilder;
    }

    public function getProperties(Relation $iRelation, ?RelationProperty $iOwnerProperty=null)
    {
        $iOwnerProperty = $iOwnerProperty ?? $iRelation->getOwnerProp();

        if($this->getType() === self::TYPE_MODELS)
        {
            return $this->getPropertiesFromModels($iOwnerProperty);
        }
        else if($this->getType() === self::TYPE_IDENTIFIERS)
        {
            return $this->getPropertiesFromIdentifiers($iRelation, $iOwnerProperty);
        }
        else if($this->getType() === self::TYPE_QUERY_BUILDER)
        {
            return $this->getPropertiesFromQuery($iRelation, $iOwnerProperty);
        }

        return null;
    }

    private function getPropertiesFromModels(RelationProperty $iOwnerProperty): array
    {
        /** @var Model[] $iModels */
        $iModels = is_array($this->iOwner) ? $this->iOwner : [$this->iOwner];

        $iProperties = array_map(function($iModel) use($iOwnerProperty)
        {
            return $iOwnerProperty->getProperties($iModel);
        }, $iModels);

        if(self::containsNonNull($iProperties)) return null;

        $mapped = [];
        foreach($iProperties as $iProperty)
        {
            $key = print_r($iProperty, true);

            $iProperties[$key] = $iProperty;
        }

        return $mapped;
    }

    private function getPropertiesFromIdentifiers(Relation $iRelation, RelationProperty $iOwnerProperty)
    {
        $ids = Utilities::normalizeIds($this->iOwner, $iOwnerProperty, ['arrayOutput'=>true]);
        
        if(self::isIdProperty($iOwnerProperty)) return $ids;

        /** @var ModelQueryBuilder $query */
        $query = call_user_func([$iRelation->getOwnerModelClass(), 'query']);

        $query->select($iOwnerProperty->getReferences($query));

        return $query;
    }

    private function getPropertiesFromQuery(Relation $iRelation, RelationProperty $iOwnerProperty)
    {
        $query = clone $this->iOwner;

        if(self::isOwnerModelClassQuery($query, $iRelation))
        {
            $query->clearSelect();
            $query->select($iOwnerProperty->getReferences($query));
        }

        return $query;
    }

    private static function isIdProperty(RelationProperty $iOwnerProperty): bool
    {
        $idProp = call_user_func([$iOwnerProperty->getModelClass(), 'getIdRelationProperty']); // TODO: Implement getIdRelationProperty() method

        return array_reduce($idProp->getProperties(), function($carry, $prop)
        {
            return $carry && $prop === null;
        }, true);
    }

    private static function containsNonNull(array $array): bool
    {
        foreach($array as $value)
        {
            if(is_array($value))
            {
                if(self::containsNonNull($value)) return true;
            }
            else if($value !== null)
            {
                return true;
            }
        }

        return false;
    }

    private static function isOwnerModelClassQuery(ModelQueryBuilder $iBuilder, Relation $iRelation): bool
    {
        $modelClass = call_user_func([$iBuilder->getModelClass(), 'class']);
        $ownerModelClass = call_user_func([$iRelation->getOwnerModelClass(), 'class']);

        return $modelClass === $ownerModelClass;
    }

    public function getSplitProps(ModelQueryBuilder $iBuilder, Relation $iRelation, RelationProperty $iOwnerProperty=null): array
    {
        $iOwnerProperty = $iOwnerProperty ?? $iRelation->getOwnerProp();

        $values = $this->getProperties($iRelation, $iOwnerProperty);

        if(!($values instanceof ModelQueryBuilder)) return $values;
        
        if($iOwnerProperty->getSize() === 1) return [[$values]];

        return [
            array_map(function($i) use ($values, $iOwnerProperty, $iBuilder) {
                $clonedValues = clone $values;
                return $clonedValues->clearSelect()->select($iOwnerProperty->ref($iBuilder, $i));
            }, range(0, $iOwnerProperty->getSize() - 1))
        ];
    }
}
