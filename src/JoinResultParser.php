<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

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
     * @var array<string, JoinResultColumn>
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
    private array $rootModels;


    public function __construct(TableTree $iTableTree, array $omitColumnAliases=[])
    {// 2023-07-31
        $this->iTableTree = $iTableTree;
        $this->omitColumnAliases = $omitColumnAliases;

        $this->columnsByTableNode = [];
        $this->parentMap = [];
        $this->rootModels = [];
    }

    public static function create(...$args)
    {// 2023-07-31
        return new static(...$args);
    }

    public function parse(array $flatRows)
    {
        if(count($flatRows) === 0) return $flatRows;

        $this->columnsByTableNode = $this->createColumns($flatRows[0]);
        $this->parentMap = [];
        $this->rootModels = [];
    
        foreach($flatRows as $flatRow)
        {
            $this->parseNode($this->iTableTree->getRootNode(), $flatRow);
        }

        return $this->rootModels;
    }

    private function parseNode($tableNode, $flatRow, $parentModel = null, $parentKey = null)
    {
        $id = $tableNode->getIdFromFlatRow($flatRow);

        if($id === null) return;

        $key = $this->getKey($parentKey, $id, $tableNode);
        if(!isset($this->parentMap[$key]))
        {
            $model = $this->createModel($tableNode, $flatRow);

            $this->addToParent($tableNode, $model, $parentModel);
            $this->parentMap[$key] = $model;
        }
        else
        {
            $model = $this->parentMap[$key];
        }

        foreach($tableNode->getChildNodes() as $childNode)
        {
            $this->parseNode($childNode, $flatRow, $model, $key);
        }
    }

    private function addToParent($tableNode, $model, &$parentModel)
    {// 2023-07-31
        if($tableNode->getParentNode())
        {
            if($tableNode->relation->isOneToOne())
            {
                $parentModel[$tableNode->relationProperty] = $model;
            }
            else
            {
                $parentModel[$tableNode->relationProperty][] = $model;
            }
        }
        else
        {
            // Root model. Add to root list.
            $this->rootModels[] = $model;
        }
    }

    private function getKey($parentKey, $id, $tableNode)
    {
        if($parentKey !== null) return $parentKey . "/" . $tableNode->relationProperty . "/" . $id;

        return "/" . $id;
    }

    private function createModel(TableNode $iTableNode, array $flatRow)
    {
        $row = [];
        $columns = $this->columnsByTableNode[$iTableNode->getUUID()];

        if($columns)
        {
            foreach($columns as $column)
            {
                if(!isset($this->omitColumnAliases[$column->getColumnAlias()]))
                {
                    $row[$column->getName()] = $flatRow[$column->getColumnAlias()];
                }
            }
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $iTableNode->getModelClass();
        $model = $modelClass::createFromDatabaseArray($row);

        foreach($iTableNode->getChildNodes() as $childNode)
        {
            $model[$childNode->relationProperty] = $childNode->relation->isOneToOne() ? null : [];
        }

        return $model;
    }

    private function createColumns($row): array
    {
        $iTableTree = $this->iTableTree;
        $columns = array_map(function($columnAlias) use ($iTableTree) {
            return JoinResultColumn::create($iTableTree, $columnAlias);
        }, array_keys($row));

        $groupedColumns = Utilities::groupBy($columns, function($item)
        {
            return $item->getTableNode()->getUUID();
        });

        return $groupedColumns;
    }



}
