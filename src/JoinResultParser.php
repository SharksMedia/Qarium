<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-31

class JoinResultParser
{
    /**
     * 2023-07-31
     * @var TableTree
     */
    private TableTree $iTableTree;

    /**
     * 2023-07-31
     * @var array<int, string>
     */
    private array $omitColumnAliases;

    /**
     * 2023-07-31
     * @var array<string, array<int, JoinResultColumn>>
     */
    private array $columnsByTableNode;

    /**
     * 2023-07-31
     * @var array<string, string>
     */
    private array $parentMap;

    /**
     * 2023-07-31
     * @var array<string, Model>
     */
    private array $iRootModels;

    /**
     * 2023-07-31
     * @param TableTree $iTableTree
     * @param array<int, string> $omitColumnAliases
     */
    public function __construct(TableTree $iTableTree, array $omitColumnAliases=[])
    {// 2023-07-31
        $this->iTableTree = $iTableTree;
        $this->omitColumnAliases = $omitColumnAliases;

        $this->columnsByTableNode = [];
        $this->parentMap = [];
        $this->iRootModels = [];
    }

    /**
     * 2023-07-31
     * @param TableTree $iTableTree
     * @param array<int, string> $omitColumnAliases
     */
    public static function create(TableTree $iTableTree, array $omitColumnAliases=[]): self
    {// 2023-07-31
        return new static($iTableTree, $omitColumnAliases);
    }

    /**
     * 2023-08-03
     * @param array<int, array<string, mixed>> $flatRows
     * @return array<string, Model>
     */
    public function parse(array $flatRows): array
    {
        if(count($flatRows) === 0) return $flatRows;

        $this->columnsByTableNode = $this->createColumns($flatRows[0]);
        $this->parentMap = [];
        $this->iRootModels = [];

        foreach($flatRows as $flatRow)
        {
            $this->parseNode($this->iTableTree->getRootNode(), $flatRow);
        }


        return $this->iRootModels;
    }

    /**
     * 2023-08-03
     * @param TableNode $iTableNode
     * @param array<string, mixed> $flatRow
     * @param Model $iParentModel
     * @param string|null $parentKey
     */
    private function parseNode(TableNode $iTableNode, array $flatRow, Model $iParentModel=null, ?string $parentKey=null): void
    {
        $id = $iTableNode->getIdFromFlatRow($flatRow);

        if($id === null) return;

        $key = $this->getKey($parentKey, $id, $iTableNode);
        $iModel = $this->parentMap[$key] ?? null;

        if($iModel === null)
        {
            $iModel = $this->createModel($iTableNode, $flatRow);

            $this->addToParent($iTableNode, $iModel, $iParentModel);
            $this->parentMap[$key] = $iModel;
        }

        foreach($iTableNode->getChildNodes() as $iChildTableNode)
        {
            $this->parseNode($iChildTableNode, $flatRow, $iModel, $key);
        }
    }

    private function addToParent(TableNode $iTableNode, Model $iModel, ?Model &$iParentModel): void
    {// 2023-07-31
        if($iTableNode->getParentNode())
        {
            $iReflectionProperty = new \ReflectionProperty($iParentModel, $iTableNode->getRelationProperty());
            $iReflectionProperty->setAccessible(true);

            if($iTableNode->getRelation()->isOneToOne())
            {
                $iReflectionProperty->setValue($iParentModel, $iModel);
            }
            else
            {
                $value = $iReflectionProperty->getValue($iParentModel);

                $value[] = $iModel;

                $iReflectionProperty->setValue($iParentModel, $value);
            }

            return;
        }

        // Root model. Add to root list.
        $this->iRootModels[] = $iModel;
    }

    private function getKey(?string $parentKey, $id, TableNode $iTableNode): string
    {
        if($parentKey !== null) return $parentKey . "/" . $iTableNode->getRelationProperty() . "/" . $id;

        return "/" . $id;
    }

    /**
     * 2023-08-03
     * @param TableNode $iTableNode
     * @param array<string, mixed> $flatRow
     * @return Model
     */
    private function createModel(TableNode $iTableNode, array $flatRow): Model
    {
        $row = [];
        $columns = $this->columnsByTableNode[$iTableNode->getUUID()] ?? [];

        if(count($columns) !== 0)
        {
            foreach($columns as $iColumn)
            {
                if(!isset($this->omitColumnAliases[$iColumn->getColumnAlias()]))
                {
                    $row[$iColumn->getName()] = $flatRow[$iColumn->getColumnAlias()];
                }
            }
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $iTableNode->getModelClass();

        /** @var Model $model */
        $model = $modelClass::createFromDatabaseArray($row);

        foreach($iTableNode->getChildNodes() as $iChildTableNode)
        {
            $iReflectionProperty = new \ReflectionProperty($model, $iChildTableNode->getRelationProperty());
            $iReflectionProperty->setAccessible(true);

            $iReflectionProperty->setValue($model, $iChildTableNode->getRelation()->isOneToOne() ? null : []);
        }

        return $model;
    }

    /**
     * 2023-08-03
     * @param array<string, mixed> $row
     * @return array<string, JoinResultColumn[]>
     */
    private function createColumns($row): array
    {
        $iTableTree = $this->iTableTree;
        $iColumns = array_map(function(string $columnAlias) use ($iTableTree)
            {
            return JoinResultColumn::create($iTableTree, $columnAlias);
        }, array_keys($row));

        $groupedColumns = Utilities::groupBy($iColumns, function(JoinResultColumn $iColumn)
        {
            return $iColumn->getTableNode()->getUUID();
        });

        return $groupedColumns;
    }



}
