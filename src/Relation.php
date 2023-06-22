<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-06-16

class Relation
{
    public const TYPE_BELONGS_TO_ONE        = 'BELONGS_TO_ONE';
    public const TYPE_HAS_MANY              = 'HAS_MANY';
    public const TYPE_AS_ONE                = 'AS_ONE';
    public const TYPE_MANY_TO_MANY          = 'MANY_TO_MANY';
    public const TYPE_HAS_ONE_THROUGH       = 'HAS_ONE_THROUGH';

    /**
     * 2023-06-16
     * @var string
     */
    private string $name;

    /**
     * 2023-06-16
     * TYPE_BELONGS_TO_ONE | TYPE_HAS_MANY | TYPE_AS_ONE | TYPE_MANY_TO_MANY | TYPE_HAS_ONE_THROUGH
     * @var string
     */
    private string $type;

    /**
     * 2023-06-16
     * @var class-string<Model>
     */
    private string $owningModelClass;

    /**
     * 2023-06-16
     * @var class-string<Model>
     */
    private ?string $relatedModelClass;

    /**
     * 2023-06-16
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * 2023-06-20
     * @var array<string, Relation>
     */
    private array $iChildRelations = [];

    /**
     * 2023-06-21
     * @var Relation|null
     */
    private ?Relation $iParentRelation = null;

    /**
     * 2023-06-22
     * @var ModelQueryBuilder|null
     */
    private ?ModelQueryBuilder $fromTableQueryBuilder = null;

    /**
     * 2023-06-16
     * @param string $relationName
     * @param string $owningModelClass
     */
    public function __construct(string $relationName, string $owningModelClass, string $relatedModelClass)
    {
        $this->name = $relationName;
        $this->owningModelClass = $owningModelClass;
        $this->relatedModelClass = $relatedModelClass;

        $relation = $this->getMappingRelation();

        $this->type = $relation['relation'];
    }

    public function getJoinOperation(): string
    {
        $options = $this->getOptions();

        return $options['joinOperation'] ?? 'leftJoin';
    }

    public function setOptions(array $options): void
    {// 2023-06-20
        $options['aliases'] = $options['aliases'] ?? [];
        $options['aliases'][$this->getName()] = $options['aliases'][$this->getName()] ?? $this->getName();

        $this->options = $options;
    }

    public function getOptions(): array
    {// 2023-06-20
        return $this->options;
    }

    /**
     * 2023-06-16
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 2023-06-16
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 2023-06-20
     * @return Model|string|null
     */
    public function addChildRelation(Relation $iRelation): void
    {// 2023-06-20
        $this->iChildRelations[$iRelation->getName()] = $iRelation;

        $iRelation->setParentRelation($this);
    }

    /**
     * 2023-06-20
     * @return array<string, Relation>
     */
    public function getChildRelations(): array
    {
        return $this->iChildRelations;
    }

    public function getParentRelation(): ?Relation
    {// 2023-06-21
        return $this->iParentRelation;
    }

    private function setParentRelation(Relation $iRelation): void
    {// 2023-06-21
        $this->iParentRelation = $iRelation;
    }

    /**
     * 2023-06-20
     * @return class-string<Model>|null
     */
    public function getRelatedModelClass(): ?string
    {
        return $this->relatedModelClass;
    }

    /**
     * 2023-06-20
     * @return class-string<Model>|null
     */
    public function getOwningModelClass(): string
    {
        return $this->owningModelClass;
    }

    public function getMappingRelation(): array
    {
        $owningModelClass = $this->getOwningModelClass();
        $relationsMap = $owningModelClass::getRelationMappings();

        $relation = $relationsMap[$this->getName()];

        return $relation;
    }

    /**
     * 2023-06-20
     * @return string|Raw
     */
    public function getFromColumn(?string $tableAlias=null)
    {
        if($tableAlias === null && $this->getParentRelation() !== null)
        {
            $parentRelation = $this->getParentRelation();

            $tableAlias = $parentRelation->getAlias();
        }

        $relation = $this->getMappingRelation();

        $column = $relation['join']['from'];

        if($tableAlias !== null) return self::tablePrefixColumnName($column, $tableAlias);

        return $column;
    }

    public function getThroughFromColumn(?string $tableAlias=null)
    {
        $relation = $this->getMappingRelation();

        $column = $relation['join']['through']['from'];

        if($tableAlias !== null) return self::tablePrefixColumnName($column, $tableAlias);

        return $column;
    }

    /**
     * 2023-06-20
     * @return string|Raw
     */
    public function getToColumn(?string $tableAlias=null)
    {
        $relation = $this->getMappingRelation();

        $column = $relation['join']['to'];

        if($tableAlias !== null) return self::tablePrefixColumnName($column, $tableAlias);

        return $column;
    }

    public function getThroughToColumn(?string $tableAlias=null)
    {
        $relation = $this->getMappingRelation();

        $column = $relation['join']['through']['to'];

        if($tableAlias !== null) return self::tablePrefixColumnName($column, $tableAlias);

        return $column;
    }

    public function getThroughExtras(): array
    {
        $relation = $this->getMappingRelation();

        return $relation['join']['through']['extras'] ?? [];
    }

    private static function tablePrefixColumnName(string $column, string $table)
    {// 2023-06-15
        $parts = explode('.', $column);

        if(count($parts) === 1) return $table.'.'.$column;

        return $table.'.'.$parts[1];
    }

    public function getAlias(): string
    {// 2023-06-21
        $options = $this->options;

        $aliasParts = [];
        if($this->getParentRelation() !== null)
        {
            $aliasParts[] = $this->getParentRelation()->getAlias();
        }

        $aliasParts[] = $options['aliases'][$this->getName()];

        return implode(':', array_filter($aliasParts));
    }

    private static function getTableNameFromColumn(string $column): ?string
    {
        $parts = explode('.', $column);

        if(count($parts) === 1) return null;

        return $parts[0];
    }

    /**
     * 2023-06-22
     * @return ModelQueryBuilder|string
     */
    public function getJoinTable(?string $aliasPrefix=null)
    {
        $relatedModelClass = $this->getRelatedModelClass();

        $relatedTableName = $relatedModelClass::getTableName();
        $relationName = $this->getJoinTableAlias($aliasPrefix);

        if($this->fromTableQueryBuilder !== null) return $this->fromTableQueryBuilder->as($relationName);

        return $relatedTableName.' AS '.$relationName;
    }

    /**
     * 2023-06-22
     * @return ModelQueryBuilder|string
     */
    public function getJoinThroughTable(?string $aliasPrefix=null): string
    {
        $throughTableName = self::getTableNameFromColumn($this->getThroughFromColumn());
        $throughRelationName = $this->getJoinThroughTableAlias($aliasPrefix);

        return $throughTableName.' AS '.$throughRelationName;
    }

    public function getJoinTableAlias(?string $aliasPrefix=null): string
    {
        $relationName = implode(':', array_filter([$aliasPrefix, $this->getName()]));

        return $relationName;
    }

    public function getJoinThroughTableAlias(?string $aliasPrefix=null): string
    {
        $throughRelationName = implode(':', array_filter([$aliasPrefix, $this->getName()])).'_through';

        return $throughRelationName;
    }

    /**
     * 2023-06-22
     * @paran ModelQueryBuilder $iModelQueryBuidler
     */
    public function setTableFromQueryBuilder(ModelQueryBuilder $iModelQueryBuidler): void
    {
        $this->fromTableQueryBuilder = $iModelQueryBuidler;
    }
}
