<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Objection\Relations;

use Sharksmedia\Objection\Model;

class Relation
{

    /**
     * 2023-07-10
     * @var string
     */
    protected string $name;

    /**
     * 2023-07-10
     * @var string
     */
    protected string $ownerModelClass;

    /**
     * 2023-07-10
     * @var string
     */
    protected string $relatedModelClass;

    /**
     * 2023-07-10
     * @var RelationProperty
     */
    protected RelationProperty $iOwnerProperty;

    /**
     * 2023-07-10
     * @var RelationProperty
     */
    protected RelationProperty $iRelatedProperty;

    /**
     * 2023-07-10
     * @var class-string<Model>
     */
    protected string $joinTableModelClass;

    /**
     * 2023-07-10
     * @var RelationProperty
     */
    protected RelationProperty $joinTableOwnerProp;

    /**
     * 2023-07-10
     * @var RelationProperty
     */
    protected RelationProperty $joinTableRelatedProp;

    /**
     * 2023-07-10
     * @var callable|null
     */
    protected ?callable $joinTableBeforeInsert;

    /**
     * 2023-07-10
     * @var array
     */
    protected array $joinTableExtras;

    /**
     * 2023-07-10
     * @var array
     */
    protected static function getForbiddenMappingProperties(): array
    {
        return ['join.through'];
    }

    /**
     * 2023-07-10
     * @param array $rawRelation
     * @param class-string<Model> $modelClass
     * @return Relation
     */
    public final static function create(array $rawRelation, string $modelClass): self
    {
        $relationType = $rawRelation['relation'] ?? null;

        if($relationType === null) throw new \Exception("Relation type is not defined");

        switch($relationType)
        {
            case Model::BELONGS_TO_ONE_RELATION:
                return new BelongsToOne($rawRelation, $modelClass);
            case Model::HAS_MANY_RELATION:
                return new HasMany($rawRelation, $modelClass);
            case Model::HAS_ONE_RELATION:
                return new HasOne($rawRelation, $modelClass);
            case Model::MANY_TO_MANY_RELATION:
                return new ManyToMany($rawRelation, $modelClass);
            case Model::HAS_ONE_THROUGH_RELATION:
                return new HasOneThrough($rawRelation, $modelClass);
            default:
                throw new \Exception("Relation type $relationType is not supported");
        }
    }

    public function getJoinTable(): string
    {
        return $this->joinTableModelClass::getTableName();
    }



}
