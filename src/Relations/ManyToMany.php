<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Objection\Relations;

use Sharksmedia\Objection\Exceptions\ModelNotFoundError;
use Sharksmedia\Objection\JoinBuilder;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelJoinBuilder;

class ManyToMany extends Relation
{
    /**
     * 2023-08-03
     * @return array<int, string>
     */
    protected static function getForbiddenMappingProperties(): array
    {
        return [];
    }

    private static function defaultJoinOperation(): string
    {
        return 'join';
    }

    private static function defaultRelatedTableAlias(self $iRelation, ModelQueryBuilder $iBuilder): string
    {
        return $iBuilder->getTableRefFor($iRelation->relatedModelClass);
    }

    private static function defaultRelatedJoinSelectQuery(self $iRelation, ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        return $iRelation->relatedModelClass::query()->childQueryOf($iBuilder);
    }

    private static function defaultRelatedTable(self $iRelation, ModelQueryBuilder $iBuilder): string
    {
        return $iBuilder->getTableNameFor($iRelation->relatedModelClass);
    }

    private static function defaultOwnerTable(self $iRelation, ModelQueryBuilder $iBuilder): string
    {
        return $iBuilder->getTableNameFor($iRelation->ownerModelClass);
    }

    private static function defaultJoinTableAlias(self $iRelation, string $relatedTableAlias, ModelQueryBuilder $iBuilder): string
    {
        $alias = null;

        if($iRelation->getJoinTable() !== null) $alias = $iBuilder->getTableRefFor($iRelation->getJoinTable());

        if($alias === $iRelation->joinTableModelClass) return $iRelation->ownerModelClass::getJoinTableAlias($relatedTableAlias);

        return $alias;
    }

    public function setMapping(array $rawRelation): void
    {
        parent::setMapping($rawRelation);

        $context = (object)
        [
            'mapping'=>$rawRelation,
            'ownerModelClass'=>$this->ownerModelClass,
            'relatedModelClass'=>$this->relatedModelClass,
            'iOwnerProperty'=>$this->iOwnerProperty,
            'iRelatedProperty'=>$this->iRelatedProperty,

            'joinTableModelClass'=>null,
            'joinTableOwnerProp'=>null,
            'joinTableRelatedProp'=>null,
            'joinTableBeforeInsert'=>null,
            'joinTableExtras'=>[],
            'createError'=>function($msg) { return $this->createError($msg); },
        ];

        $context = self::checkThroughObject($context);
        $context = self::resolveJoinModelClassIfDefined($context);
        $context = self::createJoinProperties($context);
        $context = self::parseExtras($context);
        $context = self::parseModify($context);
        $context = self::parseBeforeInsert($context);
        $context = self::finalizeJoinModelClass($context);

        $this->joinTableExtras = $context->joinTableExtras;
        $this->joinTableModify = $context->joinTableModify;
        $this->joinTableModelClass = $context->joinTableModelClass;
        $this->joinTableOwnerProp = $context->joinTableOwnerProp;
        $this->joinTableRelatedProp = $context->joinTableRelatedProp;
        $this->joinTableBeforeInsert = $context->joinTableBeforeInsert;
    }

    private static function checkThroughObject(object $context): object
    {
        $mapping = $context->mapping;

        if(!isset($mapping['join']['through']))
        {
            throw call_user_func($context->createError, 'join must have a `through` object that describes the join table.');
        }

        if(!isset($mapping['join']['through']['from']) || !isset($mapping['join']['through']['to']))
        {
            throw call_user_func($context->createError, 'join.through must be an object that describes the join table. For example: {from: "JoinTable.someId", to: "JoinTable.someOtherId"}');
        }

        return $context;
    }

    private static function resolveJoinModelClassIfDefined(object $context): object
    {
        $context->joinTableModelClass = $context->mapping['join']['through']['modelClass'] ?? null;

        return $context;
    }

    protected static function createJoinProperties(object $context): object
    {
        $iFromProp = $iToProp = $iRelatedProp = $iOwnerProp = null;

        $iFromProp = self::createRelationProperty($context, $context->mapping['join']['through']['from'], 'join.through.from');
        $iToProp = self::createRelationProperty($context, $context->mapping['join']['through']['to'], 'join.through.to');

        if($iFromProp->getModelClass()::getTableName() !== $iToProp->getModelClass()::getTableName())
        {
            throw call_user_func($context->createError, 'join.through `from` and `to` must point to the same join table.');
        }

        if($context->iRelatedProperty->getModelClass()::getTableName() === $iFromProp->getModelClass()::getTableName())
        {
            $iRelatedProp = $iFromProp;
            $iOwnerProp = $iToProp;
        }
        else
        {
            $iRelatedProp = $iToProp;
            $iOwnerProp = $iFromProp;
        }

        $context->joinTableOwnerProp = $iOwnerProp;
        $context->joinTableRelatedProp = $iRelatedProp;

        return $context;
    }

    protected static function createRelationProperty(object &$context, string $refString, ?string $propName=null): RelationProperty
    {
        $joinTableModelClass = $context->joinTableModelClass;

        $resolveModelClass = function(string $table) use (&$joinTableModelClass, $context)
        {
            if($joinTableModelClass === null)
            {
                // $joinTableModelClass = $this->inheritModel($this->getModel());
                // $joinTableModelClass = Model::class;            // FIXME: This might be broken

                $joinTableModelClass = self::resolveModel($table, $context->mapping['join']['through']['modelClass'] ?? null);

                // $joinTableModelClass::class;
                $joinTableModelClass::$tableName = $table;
                $joinTableModelClass::$idColumn = [];
                $joinTableModelClass::$concurrency = $context->ownerModelClass::$concurrency;
            }

            // var_dump($joinTableModelClass, $table);

            // if($joinTableModelClass::getTableName() !== $table) return null;

            return $joinTableModelClass;
        };

        try
        {
            $iRelationProperty = new RelationProperty($refString, $resolveModelClass);

            $context->joinTableModelClass = $joinTableModelClass;

            return $iRelationProperty;
        }
        catch(\Exception $error)
        {
            if($error instanceof ModelNotFoundError)
            {
                throw call_user_func($context->createError, 'join.through `from` and `to` must point to the same join table.');
            }

            throw call_user_func($context->createError, $messagePrefix . ' must have format JoinTable.columnName. For example "JoinTable.someId" or in case of composite key ["JoinTable.a", "JoinTable.b"].');
        }
    }

    private static function parseExtras(object $context): object
    {
        $extraDef = $context->mapping['join']['through']['extras'] ?? null;

        if($extraDef === null) return $context;

        if(is_string($extraDef))
        {
            $extraDef = [$extraDef=>$extraDef];
        }
        else if(is_array($extraDef))
        {
            $temp = [];
            foreach($extraDef as $col) $temp[$col] = $col;

            $extraDef = $temp;
        }

        $joinTableExtras = array_map(function(string $key) use ($context, $extraDef)
            {
                $val = $extraDef[$key];

                $joinTableModelClass = $context->joinTableModelClass;

                $joinTableExtra = (object)
                [
                    'joinTableCol'=>$val,
                    'joinTableProp'=>$joinTableModelClass ? $joinTableModelClass::columnNameToPropertyName($val) : null,
                    'aliasCol'=>$key,
                    'aliasProp'=>$joinTableModelClass ? $joinTableModelClass::columnNameToPropertyName($key) : null,
                ];

                return $joinTableExtra;
            }, array_keys($extraDef));

        $context->joinTableExtras = $joinTableExtras;

        return $context;
    }

    protected static function parseModify(object $context): object
    {
        $mapping = $context->mapping['join']['through'];
        $modifier = $mapping['modify'] ?? $mapping['filter'] ?? null;
        $joinTableModify = null;

        if($modifier !== null)
        {
            $joinTableModify = self::createModifier(['modifier'=>$modifier, 'modelClass'=>$context->relatedModelClass]);
        }
        
        $context->joinTableModify = $joinTableModify;

        return $context;
    }

    protected static function parseBeforeInsert(object $context): object
    {
        $joinTableBeforeInsert = null;
        if($context->mapping['join']['through']['beforeInsert'] ?? null instanceof \Closure)
        {
            $joinTableBeforeInsert = $context->mapping['join']['through']['beforeInsert'];
        }
        else
        {
            $joinTableBeforeInsert = function($model) { return $model;};
        }

        $context->joinTableBeforeInsert = $joinTableBeforeInsert;

        return $context;
    }

    private static function finalizeJoinModelClass(object $context): object
    {
        if($context->joinTableModelClass !== null && count($context->joinTableModelClass::getTableIDs()) === 0)
        {
            $context->joinTableModelClass::$idColumn = $context->joinTableRelatedProp->getColumns();
        }
        
        return $context;
    }

    public function join(ModelQueryBuilder $iBuilder, ?string $joinOperation=null, ?string $relatedTableAlias=null, ?ModelQueryBuilder $relatedJoinSelectQuery=null, ?string $relatedTable=null, ?string $ownerTable=null): ModelQueryBuilder
    {
        $joinOperation = $joinOperation ?? self::defaultJoinOperation($this, $iBuilder);
        $relatedTableAlias = $relatedTableAlias ?? self::defaultRelatedTableAlias($this, $iBuilder);
        $relatedJoinSelectQuery = $relatedJoinSelectQuery ?? self::defaultRelatedJoinSelectQuery($this, $iBuilder);
        $relatedTable = $relatedTable ?? self::defaultRelatedTable($this, $iBuilder);
        $ownerTable = $ownerTable ?? self::defaultOwnerTable($this, $iBuilder);

        $joinTableAlias = self::defaultJoinTableAlias($this, $relatedTableAlias, $iBuilder);

        $relatedJoinSelect = $this->applyModify($relatedJoinSelectQuery)->as($relatedTableAlias);

        if($relatedJoinSelect->isSelectAll())
        {
            // No need to join a subquery if the query is `select * from "RelatedTable"`.
            $relatedJoinSelect = $this->aliasedTableName($relatedTable, $relatedTableAlias);
        }

        $joinTableSelect = $this->joinTableModelClass::query()
            ->childQueryOf($iBuilder)
            ->modify($this->joinTableModify)
            ->as($joinTableAlias);

        if($joinTableSelect->isSelectAll())
        {
            $joinTableSelect = $this->aliasedTableName($this->getJoinTable(), $joinTableAlias);
        }

        return $iBuilder->{$joinOperation}($joinTableSelect, function(JoinBuilder $iJoin) use($iBuilder, $joinTableAlias, $ownerTable)
            {
                $iOwnerProperty = $this->iOwnerProperty;
                $joinTableOwnerProp = $this->joinTableOwnerProp;

                foreach($iOwnerProperty->getReferences() as $i=>$r)
                {
                    $joinTableOwnerRef = $joinTableOwnerProp->ref($iBuilder, $i)->table($joinTableAlias);
                    $ownerRef = $iOwnerProperty->ref($iBuilder, $i)->table($ownerTable);

                    $iJoin->on($joinTableOwnerRef, $ownerRef);
                }
            })
            ->{$joinOperation}($relatedJoinSelect, function(JoinBuilder $iJoin) use($iBuilder, $joinTableAlias, $relatedTableAlias)
            {
                $iRelatedProperty = $this->iRelatedProperty;
                $joinTableRelatedProp = $this->joinTableRelatedProp;

                foreach($iRelatedProperty->getReferences() as $i=>$r)
                {
                    $joinTableRelatedRef = $joinTableRelatedProp->ref($iBuilder, $i)->table($joinTableAlias);
                    $relatedRef = $iRelatedProperty->ref($iBuilder, $i)->table($relatedTableAlias);

                    $iJoin->on($joinTableRelatedRef, $relatedRef);
                }

            });
    }

    private static function resolveModel(string $tableName, ?string $modelClass=null): string
    {
        if($modelClass !== null) return $modelClass::class;

        $iModel = new class extends Model
        {
            public function isAnonymous(): bool
            {
                return true;
            }
        };

        // WARN: Table name could be an already existing class name. which would cause a fatal error.
        class_alias(get_class($iModel), $tableName);

        return $tableName;
    }
}
