<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\Operations\Selection;
use Sharksmedia\Qarium\Relations\ManyToMany;

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
            'joinOperation' => 'leftJoin',
            'minimize'      => false,
            'separator'     => ':',
            'aliases'       => [],
        ];

        return $defaultOptions;
    }

    /**
     * Fetches the column information needed for building the select clauses.
     *
     * This must be called before calling `build(builder, true)`. `build(builder, false)`
     * can be called without this since it doesn't build selects.
     */
    public function fetchColumnInfo(ModelSharQ $iBuilder): array
    {
        $iTableTree      = $this->getTableTree($iBuilder);
        $allModelClasses = array_map(function($iNode)
        { return $iNode->getModelClass(); }, $iTableTree->getNodes());
        $allModelClasses = array_unique($allModelClasses);

        $columnInfos = [];

        foreach ($allModelClasses as $modelClass)
        {
            $columnInfo = $modelClass::fetchTableMetadata();

            $columnInfos[$modelClass] = $columnInfo;
        }

        return $columnInfos;
    }
    
    private function getTableTree(ModelSharQ $iBuilder): TableTree
    {
        if ($this->iTableTree === null)
        {
            $this->iTableTree = TableTree::create($this->expression, $this->rootModelClass, $iBuilder->getTableRef(), $this->options);
        }

        return $this->iTableTree;
    }

    public function build(ModelSharQ &$iBuilder, bool $buildSelects = true): void
    {
        $iTableTree    = $this->getTableTree($iBuilder);
        $rootTableNode = $iTableTree->getRootNode();

        $userSelectQueries = [$rootTableNode->getUUID() => &$iBuilder];

        foreach ($rootTableNode->getChildNodes() as $iChildTableNode)
        {
            $this->buildJoinsForNode($iBuilder, $iChildTableNode, $userSelectQueries);
        }

        if ($buildSelects)
        {
            $this->buildSelects($iBuilder, $rootTableNode, $userSelectQueries);
        }
    }

    public function parseResult(ModelSharQ $iBuilder, array $flatRows)
    {
        $parser = JoinResultParser::create($this->getTableTree($iBuilder), array_column($this->internalSelections, 'alias'));

        $parsed = $parser->parse($flatRows);

        return $parsed;
    }

    private function buildSelects(ModelSharQ &$iBuilder, TableNode $iTableNode, array &$userSelectQueries): ModelSharQ
    {
        $selectionsForNode = $this->getSelectionsForNode($iBuilder, $iTableNode, $userSelectQueries);

        $selections         = $selectionsForNode['selections'];
        $internalSelections = $selectionsForNode['internalSelections'];

        foreach ($selections as $selection)
        {
            $this->checkAliasLength($iTableNode->getModelClass(), $selection->getName());
        }

        // Save the selections that were added internally (not by the user)
        // so that we can later remove the corresponding properties when
        // parsing the result.
        $this->internalSelections = $internalSelections;

        $r = $iBuilder->select($this->selectionsToStrings($selections));

        return $r;
    }


    private function getSelectionsForNode(ModelSharQ $iBuilder, TableNode $iTableNode, array &$userSelectQueries)
    {
        $userSelectQuery        = $userSelectQueries[$iTableNode->getUUID()];
        $userSelections         = $userSelectQuery->findAllSelections();
        $userSelectedAllColumns = $this->isSelectAllSelectionSet($userSelections);

        $selections         = [];
        $internalSelections = [];

        if ($iTableNode->hasParent())
        {
            $selections = $this->mapUserSelectionsFromSubqueryToMainQuery($userSelections, $iTableNode);

            if ($userSelectedAllColumns && $iTableNode->getRelation() instanceof ManyToMany)
            {
                $extraSelections = $this->getJoinTableExtraSelectionsForNode($iBuilder, $iTableNode);

                $selections = array_merge($selections, $extraSelections);
            }
        }

        if ($userSelectedAllColumns)
        {
            $allColumnSelections = $this->getAllColumnSelectionsForNode($iBuilder, $iTableNode);

            $selections = array_merge($selections, $allColumnSelections);
        }
        else
        {
            $idSelections = $this->getIdSelectionsForNode($iTableNode);

            foreach ($idSelections as $idSelection)
            {
                if (!$userSelectQuery->hasSelectionAs($idSelection->getColumn(), $idSelection->getColumn()))
                {
                    $selections[]         = $idSelection;
                    $internalSelections[] = $idSelection;
                }
            }
        }

        foreach ($iTableNode->getChildNodes() as $iChildNode)
        {
            $childResult = $this->getSelectionsForNode($iBuilder, $iChildNode, $userSelectQueries);

            $selections         = array_merge($selections, $childResult['selections']);
            $internalSelections = array_merge($internalSelections, $childResult['internalSelections']);
        }

        $result =
        [
            'selections'         => $selections,
            'internalSelections' => $internalSelections,
        ];

        return $result;
    }

    private function isSelectAllSelectionSet(array $selections): bool
    {// 2023-07-31 Generate by copilot
        if (count($selections) === 0)
        {
            return true;
        }

        foreach ($selections as $selection)
        {
            if ($this->isSelectAll($selection))
            {
                return true;
            }
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
        if (strlen($alias) <= self::ID_LENGTH_LIMIT)
        {
            return;
        }

        throw new \Exception("identifier ".$alias." is over ".self::ID_LENGTH_LIMIT." characters long and would be truncated by the database engine.");
    }

    /**
     * @param array<int, Selection> $iSelections
     * @return Selection[]
     */
    private function selectionsToStrings(array $iSelections): array
    {// 2023-07-31
        return array_map(function($iSelection)
        {
            $selectStr = $iSelection->getTable().'.'.$iSelection->getColumn();

            return $selectStr.' AS '.$iSelection->getAlias();
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

    private function getJoinTableExtraSelectionsForNode(ModelSharQ $iBuilder, TableNode $iTableNode): array
    {// 2023-07-31
        $mapped = array_map(function(object $extra) use ($iBuilder, $iTableNode)
        {
            return new Selection(
                $iTableNode->getJoinTableAlias($iBuilder),
                $extra->joinTableCol,
                $iTableNode->getColumnAliasForColumn($extra->joinTableCol)
            );
        }, $iTableNode->getRelation()->getJoinTableExtras());

        return $mapped;
    }

    private function getAllColumnSelectionsForNode(ModelSharQ $iBuilder, TableNode $iTableNode): array
    {// 2023-07-31
        $modelClass = $iTableNode->getModelClass();

        /** @var class-string<Model> $table */
        $table = $iBuilder->getTableNameFor($modelClass);

        $tableMeta = $modelClass::getTableMetadata(['table' => $table]);

        if (!$tableMeta)
        {
            throw new \Exception("table metadata has not been fetched for table '$table'. Are you trying to call toSharQQuery? To make sure the table metadata is fetched see the Qarium::initialize function.");
        }

        $columnNames = array_column($tableMeta, 'COLUMN_NAME');

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

    private function buildJoinsForNode(ModelSharQ $iBuilder, TableNode $iTableNode, array &$userSelectQueries)
    {// 2023-08-01
        $subQueryToJoin = $this->createSubqueryToJoin($iBuilder, $iTableNode, $this->modifiers);

        $userSelectQuery = clone $subQueryToJoin;

        // relation.join applies the relation modifier that can also contain selects.
        $userSelectQuery->modify($iTableNode->getRelation()->getModify());

        // Save the query that contains the user specified selects for later use.
        $userSelectQueries[$iTableNode->getUUID()] = $userSelectQuery;

        $joinOperation          = $this->options['joinOperation'];
        $relatedTableAlias      = $iTableNode->getAlias();
        $relatedJoinSelectQuery = $this->ensureIdAndRelationPropsAreSelected($subQueryToJoin, $iTableNode);
        $relatedTable           = null;
        $ownerTable             = null;

        $iTableNode->getRelation()->join($iBuilder, $joinOperation, $relatedTableAlias, $relatedJoinSelectQuery, $relatedTable, $ownerTable);

        foreach ($iTableNode->getChildNodes() as $iChilTableNode)
        {
            $this->buildJoinsForNode($iBuilder, $iChilTableNode, $userSelectQueries);
        }
    }

    private function createSubqueryToJoin(ModelSharQ $iBuilder, TableNode $iTableNode, array $modifiers): ModelSharQ
    {// 2023-08-01
        $iRelation           = $iTableNode->getRelation();
        $iRelationExpression = $iTableNode->getRelationExpression();

        /** @var class-string<Model> $modelClass */
        $modelClass = $iTableNode->getModelClass();

        $modifierQuery = $modelClass::query()->childQueryOf($iBuilder);

        foreach ($iRelationExpression->getNode()->modify as $modifierName)
        {
            $modifier = $this->createModifier($modifierName, $modelClass, $modifiers);

            $modifier($modifierQuery);
        }

        return $modifierQuery;
    }

    private function createModifier($modifier, string $modelClass, array $modifiers): \Closure
    {
        $modelModifiers = $modelClass::getModifiers();

        if (!is_array($modifier))
        {
            $modifier = [$modifier];
        }

        $modifierFunctions = array_map(function($modifier) use ($modifiers, $modelModifiers, $modelClass)
        {
            $modify = null;

            if (is_string($modifier))
            {
                $modify = $modifiers[$modifier] ?? $modelModifiers[$modifier] ?? null;

                // Modifiers can be pointers to other modifiers. Call this function recursively.
                if ($modify !== null && !($modify instanceof \Closure))
                {
                    return $this->createModifier($modify, $modelClass, $modifiers);
                }
            }
            else if ($modifier instanceof \Closure)
            {
                $modify = $modifier;
            }
            else if (is_array($modifier))
            {
                return $this->createModifier($modifier, $modelClass, $modifiers);
            }

            return $modify;
        }, $modifier);

        return function(ModelSharQ $iBuilder, ...$args) use ($modifierFunctions)
        {
            foreach ($modifierFunctions as $modifierFunction)
            {
                $modifierFunction($iBuilder, ...$args);
            }
        };
    }

    private function ensureIdAndRelationPropsAreSelected(ModelSharQ $iBuilder, TableNode $iTableNode)
    {
        $tableRef = $iBuilder->getTableRef();

        $modelClass = $iBuilder->getModelClass();

        $cols =
        [
            ...$modelClass::getTableIDs(),
            ...$iTableNode->getRelation()->getRelatedProp()->getColumns(),
            ...array_reduce($iTableNode->getChildNodes(), function($carry, $extra)
            {
                return [...$carry, ...$extra->getRelation()->getOwnerProp()->getColumns()];
            }, []),
        ];

        $selectedStrings = array_unique($cols);
        $selectedStrings = array_filter($selectedStrings, function($col) use ($iBuilder)
        {
            return !$iBuilder->hasSelectionAs($col, $col);
        });
        $selectedStrings = array_map(function($col) use ($tableRef)
        {
            return "$tableRef.$col";
        }, $selectedStrings);

        return $iBuilder->select($selectedStrings);
    }
}
