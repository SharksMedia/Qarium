<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Objection\Relations;

use Exception;
use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\Statement\Join;

use Sharksmedia\Objection\Exceptions\ModelNotFoundError;
use Sharksmedia\Objection\Exceptions\InvalidReferenceError;
use Sharksmedia\Objection\JoinBuilder;

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
     * @var \Closure|null
     */
    protected ?\Closure $modify = null;

    /**
     * 2023-07-10
     * @var class-string<Model>
     */
    protected ?string $joinTableModelClass;

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
     * @var Closure|null
     */
    protected ?\Closure $joinTableBeforeInsert;

    /**
     * 2023-07-10
     * @var array<int, object>
     */
    protected array $joinTableExtras;

    /**
     * 2023-07-10
     * @var \Closure|null
     */
    protected ?\Closure $joinTableModify = null;

    /**
     * 2023-07-10
     * @var \Closure|null
     */
    protected ?\Closure $beforeInsert = null;

    /**
     * 2023-07-10
     * @return array<int, string>
     */
    protected static function getForbiddenMappingProperties(): array
    {
        return ['join.through'];
    }

    public function __construct(string $relationName, array $rawRelation, string $ownerModelClass)
    {
        // print_r($rawRelation);
        // print_r($modelClass);

        $this->name = $relationName;
        $this->ownerModelClass = $ownerModelClass;
        $this->relatedModelClass = $rawRelation['modelClass'] ?? null;
    }

    /**
     * 2023-07-10
     * @param array $rawRelation
     * @param class-string<Model> $modelClass
     * @return Relation
     */
    public final static function create(string $relationName, array $rawRelation, string $modelClass): self
    {
        $relationType = $rawRelation['relation'] ?? null;

        if($relationType === null) throw new \Exception("Relation type is not defined");

        switch($relationType)
        {
            case Model::BELONGS_TO_ONE_RELATION:
                return new BelongsToOne($relationName, $rawRelation, $modelClass);
            case Model::HAS_MANY_RELATION:
                return new HasMany($relationName, $rawRelation, $modelClass);
            case Model::HAS_ONE_RELATION:
                return new HasOne($relationName, $rawRelation, $modelClass);
            case Model::MANY_TO_MANY_RELATION:
                return new ManyToMany($relationName, $rawRelation, $modelClass);
            case Model::HAS_ONE_THROUGH_RELATION:
                return new HasOneThrough($relationName, $rawRelation, $modelClass);
            default:
                throw new \Exception("Relation type $relationType is not supported");
        }
    }

    public function getJoinTable(): ?string
    {// 2023-08-01
        if($this->joinTableModelClass === null) return null;

        return $this->joinTableModelClass::getTableName();
    }

    public function getJoinTableExtras(): array
    {
        return $this->joinTableExtras;
    }

    public function getName(): string
    {// 2023-08-01
        return $this->name;
    }

    public function getRelatedProp(): RelationProperty
    {// 2023-08-01
        return $this->iRelatedProperty ?? new RelationProperty();
    }

    public function getOwnerProp(): RelationProperty
    {// 2023-08-01
        return $this->iOwnerProperty ?? new RelationProperty();
    }

    /**
     * @return class-string<Model>
     */
    public function getRelatedModelClass(): string
    {// 2023-08-01
        return $this->relatedModelClass;
    }

    public function getJoinTableAlias(?string $aliasPrefix=null): string
    {// 2023-08-01
        $relationName = implode(':', array_filter([$aliasPrefix, $this->getName()]));

        return $relationName;
    }

    public function getModify(): ?\Closure
    {// 2023-08-01
        return $this->modify;
    }

    public function isOneToOne(): bool
    {
        return false;
    }

    public function join(ModelQueryBuilder $iBuilder, ?string $joinOperation=null, ?string $relatedTableAlias=null, ?ModelQueryBuilder $relatedJoinSelectQuery=null, ?string $relatedTable=null, ?string $ownerTable=null): ModelQueryBuilder
    {
        $relatedModelClass = $this->getRelatedModelClass();
        $joinOperation = $joinOperation ?? 'join';
        $relatedTableAlias = $relatedTableAlias ?? $iBuilder->getTableRefFor($relatedModelClass);
        $relatedJoinSelectQuery = $relatedJoinSelectQuery ?? $relatedModelClass::query()->childQueryOf($iBuilder);
        $relatedTable = $relatedTable ?? $iBuilder->getTableNameFor($relatedModelClass);
        $ownerTable = $ownerTable ?? $iBuilder->getTableNameFor($this->ownerModelClass);

        $relatedJoinSelect = $this->applyModify($relatedJoinSelectQuery)->as($relatedTableAlias);

        if($relatedJoinSelect->isSelectAll())
        {
            // No need to join a subquery if the query is `select * from "RelatedTable"`.
            $relatedJoinSelect = $this->aliasedTableName($relatedTable, $relatedTableAlias);
        }

        return $iBuilder->{$joinOperation}($relatedJoinSelect, function(JoinBuilder $iJoin) use($iBuilder, $relatedTableAlias, $ownerTable)
            {
                $iRelatedProperty = $this->iRelatedProperty;
                $iOwnerProperty = $this->iOwnerProperty;

                foreach($iRelatedProperty->getReferences() as $i=>$r)
                {
                    $relatedRef = $iRelatedProperty->ref($iBuilder, $i)->table($relatedTableAlias);
                    $ownerRef = $iOwnerProperty->ref($iBuilder, $i)->table($ownerTable);

                    $iJoin->on($relatedRef, $ownerRef);
                }
            });
    }

    protected function applyModify(ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {// 2023-08-01
        return $iBuilder->modify($this->modify);
    }

    protected function aliasedTableName(string $tableName, string $alias): string
    {// 2023-08-01
        if($tableName === $alias) return $tableName;

        return $tableName . ' AS ' . $alias;
    }

    public function setMapping(array $rawRelation): void
    {
        $context = (object)
        [
            'name'=>$this->name,
            'mapping'=>$rawRelation,
            'ownerModelClass'=>$this->ownerModelClass,
            'relatedModelClass'=>$this->relatedModelClass,
            'iRelatedProperty'=>null,
            'iOwnerProperty'=>null,
            'modify'=>null,
            'beforeInsert'=>null,
            'forbiddenMappingProperties'=>$this->getForbiddenMappingProperties(),
            'createError'=>function(string $message){ return $this->createError($message); },
        ];

        $context = self::checkForbiddenProperties($context);
        $context = self::checkOwnerModelClass($context);
        $context = self::checkRelatedModelClass($context);
        // $context = $this->resolveRelatedModelClass($context);
        $context = self::checkRelation($context);
        $context = self::createJoinProperties($context);
        $context = self::parseModify($context);
        $context = self::parseBeforeInsert($context);

        $this->relatedModelClass = $context->relatedModelClass;
        $this->iOwnerProperty = $context->iOwnerProperty;
        $this->iRelatedProperty = $context->iRelatedProperty;
        $this->modify = $context->modify;
        $this->beforeInsert = $context->beforeInsert;
    }

    protected static function checkForbiddenProperties(object $context): object
    {// 2023-08-02
        foreach($context->forbiddenMappingProperties as $prop)
        {
            $props = explode(".", $prop);
            $val = $context->mapping;

            foreach($props as $p) $val = $val[$p] ?? null;

            if($val !== null) throw new \Exception("Property " . $prop . " is not supported for this relation type.");
        }

        return $context;
    }

    protected static function checkOwnerModelClass(object $context): object
    {// 2023-08-02
        if(!is_subclass_of($context->ownerModelClass, Model::class))
        {
            throw new \Exception("Relation's owner is not a subclass of Model (".$context->ownerModelClass.")");
        }

        return $context;
    }

    protected static function checkRelatedModelClass(object $context): object
    {// 2023-08-02
        if(!isset($context->mapping['modelClass'])) throw new \Exception('modelClass is not defined');

        return $context;
    }

    // protected function resolveRelatedModelClass(object $context): object
    // {// 2023-08-02
    //     $relatedModelClass = null;
    //
    //     try
    //     {
    //         $relatedModelClass = $this->resolveModel($context->mapping->modelClass, $context->ownerModelClass->modelPaths, 'modelClass');
    //     }
    //     catch(\Exception $err)
    //     {
    //         throw new \Exception($err->getMessage());
    //     }
    //
    //     $context->relatedModelClass = $relatedModelClass;
    //
    //     return $context;
    // }

    protected static function checkRelation(object $context): object
    {// 2023-08-02
        if(!isset($context->mapping['relation']))
        {
            throw new \Exception('relation is not defined');
        }

        if(is_subclass_of($context->mapping['relation'], Relation::class))
        {
            throw new \Exception('relation is not a subclass of Relation');
        }

        return $context;
    }

    protected static function createJoinProperties(object $context): object
    {// 2023-08-02
        $mapping = $context->mapping;

        if(!isset($mapping['join']) || !isset($mapping['join']['from']) || !isset($mapping['join']['to']))
        {
            throw new \Exception('join must be an object that maps the columns of the related models together. For example: {from: "SomeTable.id", to: "SomeOtherTable.someModelId"}');
        }

        $iFromProp = self::createRelationProperty($context, $mapping['join']['from'], 'join.from');
        $iToProp = self::createRelationProperty($context, $mapping['join']['to'], 'join.to');

        $iOwnerProperty = null;
        $iRelatedProperty = null;

        if($iFromProp->getModelClass()::getTableName() === $context->ownerModelClass::getTableName())
        {
            $iOwnerProperty = $iFromProp;
            $iRelatedProperty = $iToProp;
        }
        else if ($iToProp->getModelClass()::getTableName() === $context->ownerModelClass::getTableName())
        {
            $iOwnerProperty = $iToProp;
            $iRelatedProperty = $iFromProp;
        }
        else
        {
            throw new \Exception('join: either `from` or `to` must point to the owner model table.');
        }

        if(in_array($context->name, $iOwnerProperty->getProperties()))
        {
            throw new \Exception("join: relation name and join property '{$context->name}' cannot have the same name. If you cannot change one or the other, you can use \$parseDatabaseJson and \$formatDatabaseJson methods to convert the column name.");
        }

        if($iRelatedProperty->getModelClass()::getTableName() !== $context->relatedModelClass::getTableName())
        {
            throw new \Exception('join: either `from` or `to` must point to the related model table.');
        }

        $context->iOwnerProperty = $iOwnerProperty;
        $context->iRelatedProperty = $iRelatedProperty;

        return $context;
    }

    protected static function parseModify(object $context): object
    {// 2023-08-02
        $mapping = $context->mapping;
        $modifier = null;
        $modify = null;

        if(isset($mapping->modify))
        {
            $modifier = $mapping->modify;
        }
        else if(isset($mapping->filter))
        {
            $modifier = $mapping->filter;
        }

        if($modifier)
        {
            $modify = self::createModifier($modifier, $context->relatedModelClass);
        }

        $context->modify = $modify;

        return $context;
    }

    protected static function parseBeforeInsert(object $context): object
    {// 2023-08-02
        $beforeInsert = null;

        if(is_callable($context->mapping['beforeInsert'] ?? null))
        {
            $beforeInsert = $context->mapping['beforeInsert'];
        }
        else
        {
            $beforeInsert = function($model) { return $model; };
        }

        $context->beforeInsert = $beforeInsert;

        return $context;
    }

    protected static function createRelationProperty(object &$context, string $refString, ?string $propName=null): RelationProperty
    {// 2023-08-02
        try
        {
            $iRelationProperty = new RelationProperty($refString, function($table) use ($context)
            {
                foreach([$context->ownerModelClass, $context->relatedModelClass] as $it)
                {
                    if($it::getTableName() === $table) return $it;
                }

                return null;
            });

            return $iRelationProperty;
        }
        catch(ModelNotFoundError $error)
        {
            throw new \Exception("join: either `from` or `to` must point to the owner model table and the other one to the related table. It might be that specified table '{$error->getTableName()}' is not correct", $error->getCode(), $error);
        }
        catch(InvalidReferenceError $error)
        {
            throw new \Exception("$propName must have format TableName.columnName. For example \"SomeTable.id\" or in case of composite key [\"SomeTable.a\", \"SomeTable.b\"].", $error->getCode(), $error);
        }
        catch(\Exception $error)
        {
            throw $error;
        }
    }

    /**
     * @param class-string<Model> $modelClass
     * @param string|array|null $modifier
     * @param array $modifiers
     * @return \Closure
     */
    protected function createModifier(?string $modelClass=null, $modifier=null, array $modifiers=[]): \Closure
    {
        $modelModifiers = $modelClass ? $modelClass::getModifiers() : [];
        $modifier = is_array($modifier) ? $modifier : [$modifier];

        $modifierFunctions = array_map(function($modifier) use ($modelClass, $modifiers, $modelModifiers)
            {
                $modify = null;

                if(is_string($modifier))
                {
                    $modify = (isset($modifiers[$modifier]) ? $modifiers[$modifier] : null) ?? (isset($modelModifiers[$modifier]) ? $modelModifiers[$modifier] : null);

                    if($modify && !($modify instanceof \Closure)) return $this->createModifier($modelClass, $modify, $modifiers);
                }
                else if($modifier instanceof \Closure)
                {
                    $modify = $modifier;
                }
                else if(is_array($modifier))
                {
                    $modify = function($builder) use ($modifier) { return $builder->where($modifier); };
                }
                else
                {
                    return $this->createModifier($modelClass, $modifier, $modifiers);
                }

                if(!$modify)
                {
                    $modify = function($builder) use ($modelClass, $modifier) { return $modelClass::modifierNotFound($builder, $modifier); };
                }

                return $modify;
            }, $modifier);

        return function($builder) use ($modifierFunctions)
            {
                $args = func_get_args();
                foreach($modifierFunctions as $modifier) call_user_func_array($modifier, $args);
            };
    }

    protected function createError(string $message): \Exception
    {// 2023-08-02
        if($this->ownerModelClass && property_exists($this->ownerModelClass, 'name') && $this->name)
        {
            $name = $this->name;
            $class = $this->ownerModelClass;
            return new \Exception("$class::relationMappings::$name: $message");
        }
        else
        {
            return new \Exception(get_class($this) . ": {$message}");
        }
    }
}