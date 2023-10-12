<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-31

class JoinResultColumn
{
    private string         $columnAlias;
    private TableNode      $tableNode;
    private string         $name;

    public function __construct(string $columnAlias, TableNode $tableNode, string $name)
    {
        $this->columnAlias = $columnAlias;
        $this->tableNode   = $tableNode;
        $this->name        = $name;
    }

    public static function create(TableTree $tableTree, string $columnAlias)
    {
        $tableNode = $tableTree->getNodeForColumnAlias($columnAlias);

        return new JoinResultColumn(
            $columnAlias,
            $tableNode,
            $tableNode->getColumnForColumnAlias($columnAlias)
        );
    }

    public function getColumnAlias()
    {// 2023-07-31
        return $this->columnAlias;
    }

    public function getTableNode()
    {// 2023-07-31
        return $this->tableNode;
    }

    public function getName()
    {// 2023-07-31
        return $this->name;
    }
}

