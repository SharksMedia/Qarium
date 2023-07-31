<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\Selection;

// 2023-07-11

class RelationJoiner
{
    public const ID_LENGTH_LIMIT = 63;

    /**
     * 2023-07-11
     * @var class-string<Model>
     */
    private string $rootModelClass;

    /**
     * 2023-07-11
     * The relation expression to join.
     * @var RelationExpression
     */
    private RelationExpression $expression;

    /**
     * 2023-07-11
     * Explicit modifiers for the relation expression.
     * @var array
     */
    private array $modifiers = [];

    /**
     * 2023-07-11
     * @var array
     */
    private array $options = [];

    /**
     * 2023-07-11
     * @var array
     */
    private ?TableTree $iTableTree = null;

    /**
     * 2023-07-11
     * @var array
     */
    private ?array $internalSelections = null;

    public function __construct(array $options)
    {
        $this->rootModelClass = $options['modelClass'];

        $this->options = self::getDefaultOptions();
    }

    public function setExpression(RelationExpression $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    public function setModifiers(array $modifiers): self
    {
        $this->modifiers = $modifiers;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function getDefaultOptions(): array
    {
        $defaultOptions =
        [
            'joinOperation'=>'leftJoin',
            'minimize'=>false,
            'separator'=>':',
            'aliases'=>[],
        ];

        return $defaultOptions;
    }

    /**
     * Fetches the column information needed for building the select clauses.
     *
     * This must be called before calling `build(builder, true)`. `build(builder, false)`
     * can be called without this since it doesn't build selects.
     */
    public function fetchColumnInfo(ModelQueryBuilder $iBuilder): array
    {
        $iTableTree = $this->getTableTree($iBuilder);
        $allModelClasses = array_unique(array_column($iTableTree->getNodes(), 'modelClass'));

        $columnInfos = [];
        foreach($allModelClasses as $modelClass)
        {
            $columnInfo = $modelClass::fetchTableMetadata(['parentBuilder'=>$iBuilder]);

            $columnInfos[$modelClass] = $columnInfo;
        }

        return $columnInfos;
    }
    
    private function getTableTree(ModelQueryBuilder $iBuilder): TableTree
    {
        if($this->iTableTree === null)
        {
            $this->iTableTree = TableTree::create($this->expression, $this->rootModelClass, $iBuilder->getTableRef(), $this->options);
        }

        return $this->iTableTree;
    }

    public function build(ModelQueryBuilder $iBuilder, bool $buildSelects=true): void
    {
        $iTableTree = $this->getTableTree($iBuilder);
        $rootTableNode = $iTableTree->getRootNode();

        $userSelectQueries = [$rootTableNode->getUUID()=>$iBuilder];

        foreach($rootTableNode->iChildNodes as $child)
        {
            $this->buildJoinsForNode(['builder'=>$iBuilder, 'tableNode'=>$child, 'userSelectQueries'=>$userSelectQueries]);
        }

        if($buildSelects)
        {
            $this->buildSelects(['builder'=>$iBuilder, 'tableNode'=>$rootTableNode, 'userSelectQueries'=>$userSelectQueries]);
        }
    }

    public function parseResult(ModelQueryBuilder $iBuilder, array $flatRows)
    {
        $parser = JoinResultParser::create($this->getTableTree($iBuilder), array_column($this->internalSelections, 'alias'));

        $parsed = $parser->parse($flatRows);

        return $parsed;
    }

    private function buildSelects($data)
    {
        $builder = $data['builder'];
        /** @var TableNode $iTableNode */
        $iTableNode = $data['tableNode'];
        $userSelectQueries = $data['userSelectQueries'];

        $selectionsForNode = $this->getSelectionsForNode($data);

        $selections = $selectionsForNode['selections'];
        $internalSelections = $selectionsForNode['internalSelections'];

        foreach($selections as $selection)
        {
            $this->checkAliasLength($iTableNode->getModelClass(), $selection->getName());
        }

        // Save the selections that were added internally (not by the user)
        // so that we can later remove the corresponding properties when
        // parsing the result.
        $this->internalSelections = $internalSelections;

        return $builder->select($this->selectionsToStrings($selections));
    }


    private function getSelectionsForNode($data)
    {
        $builder = $data['builder'];
        /** @var TableNode $iTableNode */
        $iTableNode = $data['tableNode'];
        /** @var ModelQueryBuilder $userSelectQueries */
        $userSelectQueries = $data['userSelectQueries'];

        $userSelectQuery = $userSelectQueries[$iTableNode->getUUID()];
        $userSelections = $userSelectQuery->findAllSelections();
        $userSelectedAllColumns = $this->isSelectAllSelectionSet($userSelections);

        $selections = [];
        $internalSelections = [];

        if($iTableNode->hasParent())
        {
            $selections = $this->mapUserSelectionsFromSubqueryToMainQuery($userSelections, $iTableNode);

            if($userSelectedAllColumns && $iTableNode->getRelation()->getType === Relation::TYPE_MANY_TO_MANY)
            {
                $extraSelections = $this->getJoinTableExtraSelectionsForNode($builder, $iTableNode);

                $selections = array_merge($selections, $extraSelections);
            }
        }

        if($userSelectedAllColumns)
        {
            $allColumnSelections = $this->getAllColumnSelectionsForNode($builder, $iTableNode);

            $selections = array_merge($selections, $allColumnSelections);
        }
        else
        {
            $idSelections = $this->getIdSelectionsForNode($iTableNode);

            foreach($idSelections as $idSelection)
            {
                if(!$userSelectQuery->hasSelectionAs($idSelection->getColumn(), $idSelection->getColumn()))
                {
                    $selections[] = $idSelection;
                    $internalSelections[] = $idSelection;
                }
            }
        }

        foreach($iTableNode->getChildNodes() as $iChildNode)
        {
            $childResult = $this->getSelectionsForNode(['builder'=>$builder, 'tableNode'=>$iChildNode, 'userSelectQueries'=>$userSelectQueries]);

            $selections = array_merge($selections, $childResult['selections']);
            $internalSelections = array_merge($internalSelections, $childResult['internalSelections']);
        }

        $result =
        [
            'selections'=>$selections,
            'internalSelections'=>$internalSelections,
        ];

        return $result;
    }

    private function isSelectAllSelectionSet(array $selections): bool
    {// 2023-07-31 Generate by copilot
        foreach($selections as $selection)
        {
            if($this->isSelectAll($selection)) return true;
        }

        return false;
    }

    private function isSelectAll(Selection $iSelections): bool
    {// 2023-07-31
        return $iSelections->getColumn() === '*';
    }

    private function isNotSelectAll(Selection $iSelections): bool
    {// 2023-07-31
        return $iSelections->getColumn() !== '*';
    }

    private function checkAliasLength(string $modelClass, string $alias): void
    {// 2023-07-31
        if(strlen($alias) <= self::ID_LENGTH_LIMIT) return;

        throw new \Exception("identifier ".$alias." is over ".self::ID_LENGTH_LIMIT." characters long and would be truncated by the database engine.");
    }

    private function selectionsToStrings(array $iSelections): array
    {// 2023-07-31
        return array_map(function($iSelection)
        {
            $selectStr = $iSelection->getTableAlias().'.'.$iSelection->getColumn();

            return $selectStr.' as '.$iSelection->getAlias();
        }, $iSelections);
    }

    private function mapUserSelectionsFromSubqueryToMainQuery(array $userSelections, TableNode $tableNode): array
    {
        $filtered = array_filter($userSelections, [$this, 'isNotSelectAll']);

        $mapped = array_map(function($selection) use ($tableNode)
        {
            return new Selection($tableNode->getAlias(), $selection->getName(), $tableNode->getColumnAliasForColumn($selection->getName()));
        }, $filtered);

        return $mapped;
    }

    private function getJoinTableExtraSelectionsForNode(ModelQueryBuilder $iBuilder, TableNode $iTableNode): array
    {// 2023-07-31
        $mapped = array_map(function($extra) use($iBuilder, $iTableNode)
        {
                return new Selection(
                    $iTableNode->getJoinTableAlias($iBuilder),
                    $iTableNode->getRelation()->getThroughFromColumn(),
                    $iTableNode->getColumnAliasForColumn($iTableNode->getRelation()->getJoinThroughTableAlias())
                );
        }, $iTableNode->getRelation()->getThroughExtras());

        return $mapped;
    }

    private function getAllColumnSelectionsForNode(ModelQueryBuilder $iBuilder, TableNode $iTableNode): array
    {// 2023-07-31
        $modelClass = $iTableNode->getModelClass();

        /** @var class-string<Model> $table */
        $table = $iBuilder->getTableNameFor($modelClass);

        $tableMeta = $modelClass::getTableMetadata(['table'=>$table]);

        if(!$tableMeta) throw new \Exception("table metadata has not been fetched for table '$table'. Are you trying to call toQueryBuilderQuery? To make sure the table metadata is fetched see the Objection::initialize function.");

        $columnNames = array_column($tableMeta, 'Field');

        $selections = array_map(function($columnName) use ($iTableNode)
        {
            return new Selection($iTableNode->getAlias(), $columnName, $iTableNode->getColumnAliasForColumn($columnName));
        }, $columnNames);

        return $selections;
    }

    private function getIdSelectionsForNode(TableNode $iTableNode): array
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $iTableNode->getModelClass();

        $modelClassIDs = $modelClass::getTableIDs();

        $selections = array_map(function($columnName) use ($iTableNode)
        {
            return new Selection($iTableNode->getAlias(), $columnName, $iTableNode->getColumnAliasForColumn($columnName));
        }, $modelClassIDs);

        return $selections;
    }

}
