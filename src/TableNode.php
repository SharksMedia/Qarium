<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\Relations\Relation;
use Sharksmedia\Qarium\RelationExpression;
use Sharksmedia\Qarium\Relations\ManyToMany;
use Sharksmedia\Qarium\Utilities;

// 2023-07-12

class TableNode
{
    /**
     * 2023-07-12
     * @var TableTree
     */
    private TableTree $iTableTree;

    /**
     * 2023-07-12
     * @var class-string<Model>
     */
    private string $modelClass;

    /**
     * 2023-07-12
     * @var RelationExpression
     */
    private RelationExpression $iRelationExpression;

    /**
     * 2023-07-12
     * @var TableNode|null
     */
    private ?TableNode $iParentTableNode;

    /**
     * 2023-07-12
     * @var Relation|null
     */
    private ?Relation $iRelation;

    /**
     * 2023-07-12
     * @var array<int, TableNode>
     */
    public array $iChildNodes = [];

    /**
     * 2023-07-12
     * @var string
     */
    private string $alias;

    /**
     * 2023-07-12
     * @var \Closure
     */
    private \Closure $idGetter;

    /**
     * 2023-07-31
     * @var string
     */
    private string $uuid;

    public function __construct(TableTree $iTableTree, string $modelClass, RelationExpression $iRelationExpression, ?TableNode $iParentTableNode=null, ?Relation $iRelation=null)
    {
        $this->iTableTree = $iTableTree;
        $this->modelClass = $modelClass;
        $this->iRelationExpression = $iRelationExpression;
        $this->iParentTableNode = $iParentTableNode;
        $this->iRelation = $iRelation;

        $this->alias = $this->calculateAlias();
        $this->idGetter = $this->createIdGetter();
    }

    public static function create(TableTree $iTableTree, string $modelClass, RelationExpression $iRelationExpression, ?TableNode $iParentTableNode=null, ?Relation $iRelation=null): self
    {
        $iTableNode = new self($iTableTree, $modelClass, $iRelationExpression, $iParentTableNode, $iRelation);

        if($iTableNode->hasParent()) $iTableNode->iParentTableNode->iChildNodes[] = $iTableNode;

        return $iTableNode;
    }

    public function getOptions(): array
    {
        return $this->iTableTree->getOptions();
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function hasParent(): bool
    {// 2023-07-31
        return $this->iParentTableNode !== null;
    }

    public function getParentNode(): ?TableNode
    {// 2023-07-31
        return $this->iParentTableNode;
    }

    /**
     * 2023-07-31
     * @return array<int, TableNode>
     */
    public function getChildNodes(): array
    {
        return $this->iChildNodes;
    }

    public function getRelation(): ?Relation
    {// 2023-07-31
        return $this->iRelation;
    }

    /**
     * 2023-07-31
     * @return class-string<Model>
     */
    public function getModelClass(): string
    {// 2023-07-31
        return $this->modelClass;
    }

    public function getRelationExpression(): RelationExpression
    {// 2023-07-31
        return $this->iRelationExpression;
    }

    public function getRelationProperty(): ?string
    {
        return $this->iRelationExpression->getNode()->name;
    }

    public function getReferenceForColumn(string $column): string
    {
        return $this->alias.'.'.$column;
    }

    public function getColumnAliasForColumn(string $column): string
    {
        if($this->iParentTableNode !== null) return $this->alias.($this->getOptions()['seperator'] ?? ':').$column;

        return $column;
    }

    public function getColumnForColumnAlias(string $columnAlias): string
    {
        // FIXME: Use default options on relation joiner
        $lastSepIndex = strrpos($columnAlias, $this->getOptions()['seperator'] ?? ':');

        if($lastSepIndex === false) return $columnAlias;

        $alias = substr($columnAlias, $lastSepIndex + strlen($this->getOptions()['seperator'] ?? ':'));

        return $alias;
    }

    /**
     * 2023-07-12
     * @param array<string, string> $flatRow
     * @return string|int|null
     */
    public function getIdFromFlatRow(array $flatRow)
    {
        $idGetterFunc = $this->idGetter;

        return $idGetterFunc($flatRow);
    }

    public function getJoinTableAlias(ModelSharQ $iBuilder): ?string
    {
        if($this->iRelation instanceof ManyToMany)
        {
            return $iBuilder->getAliasFor($this->iRelation->getRelatedModelClass()) ?? $this->modelClass::getJoinTableAlias($this->alias);
        }

        return null;
    }

    private function calculateAlias()
    {
        if($this->iParentTableNode === null) return $this->iTableTree->getRootTableAlias();

        $options = $this->getOptions();

        $relationName = $this->iRelationExpression->getNode()->name;
        $alias = $options['aliases'][$relationName] ?? $relationName;

        if($options['minimize']) return '_t'.$this->iTableTree->createNextUid();

        if($this->iParentTableNode->iParentTableNode !== null) return $this->iParentTableNode->alias.($options['seperator'] ?? ':').$alias;

        return $alias;
    }

    public function getUUID(): string
    {// 2023-07-31
        $this->uuid = $this->uuid ?? Utilities::uuid();

        return $this->uuid;
    }

    /**
     * 2023-07-12
     * @return \Closure(array $flatRow): string|null
     */
    private function createIdGetter(): \Closure
    {
        $idColumns = $this->modelClass::getIdColumnArray();
        $columnAliases = array_map([$this, 'getColumnAliasForColumn'], $idColumns);
        
        if(count($idColumns) === 1) return self::_createIdGetter($columnAliases);

        return self::_createCompositeIdGetter($columnAliases);
    }

    /**
     * 2023-07-12
     * @param array<int, string> $columnAliases
     * @return \Closure(array $flatRow): string|null
     */
    private static function _createIdGetter(array $columnAliases): \Closure
    {
        $columnAlias = $columnAliases[0];

        return function($flatRow) use($columnAlias)
        {
            $id = $flatRow[$columnAlias] ?? null;

            return $id;
        };
    }

    /**
     * 2023-07-12
     * @param array<int, string> $columnAliases
     * @return \Closure(array $flatRow): string|null
     */
    private static function _createCompositeIdGetter(array $columnAliases): \Closure
    {
        if(count($columnAliases) === 2) return self::_createTwoIdGetter($columnAliases);

        return self::_createMultiIdGetter($columnAliases);
    }

    /**
     * 2023-07-12
     * @param array<int, string> $columnAliases
     * @return \Closure(array $flatRow): string|null
     */
    private static function _createTwoIdGetter(array $columnAliases): \Closure
    {
        $columnAlias1 = $columnAliases[0];
        $columnAlias2 = $columnAliases[1];

        return function($flatRow) use($columnAlias1, $columnAlias2)
        {
            $id1 = $flatRow[$columnAlias1] ?? null;
            $id2 = $flatRow[$columnAlias2] ?? null;

            if($id1 === null || $id2 === null) return null;

            return $id1.','.$id2;
        };
    }

    /**
     * 2023-07-12
     * @param array<int, string> $columnAliases
     * @return \Closure(array $flatRow): string|null
     */
    private static function _createMultiIdGetter(array $columnAliases): \Closure
    {
        return function($flatRow) use($columnAliases)
        {
            $idStr = '';

            foreach($columnAliases as $i=>$columnAlias)
            {
                $id = $flatRow[$columnAlias] ?? null;

                if($id === null) return null;

                $idStr .= $id;

                if($i < count($columnAliases) - 1) $idStr .= ',';
            }

            return $idStr;
        };
    }














}
