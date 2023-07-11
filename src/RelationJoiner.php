<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-11

class RelationJoiner
{
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
    private ?array $tableTree = null;

    /**
     * 2023-07-11
     * @var array
     */
    private ?array $internalSelections = null;

    public function __construct(array $options)
    {
        $this->rootModelClass = $options['modelClass'];

        $this->options = self::defaultOptions();
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

    /**
     * Fetches the column information needed for building the select clauses.
     *
     * This must be called before calling `build(builder, true)`. `build(builder, false)`
     * can be called without this since it doesn't build selects.
     */
    public function fetchColumnInfo(ModelQueryBuilder $iBuilder): array
    {
        $tableTree = $this->getTableTree($iBuilder);
        $allModelClasses = array_unique(array_column($tableTree, 'modelClass'));

        $columnInfos = [];
        foreach($allModelClasses as $modelClass)
        {
            $columnInfo = $modelClass::fetchTableMetadata(['parentBuilder'=>$iBuilder]);

            $columnInfos[$modelClass] = $columnInfo;
        }

        return $columnInfos;
    }

    public function build(ModelQueryBuilder $iBuilder, bool $buildSelects=true): void
    {
        $tableTree = $this->getTableTree($iBuilder);
        $rootTableNode = $tableTree['rootNode'];

        $userSelectQueries = [[$rootTableNode, $iBuilder]];

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
        $parser = JoinResultParser::create(['tableTree'=>$this->getTableTree($iBuilder), 'omitColumnAliases'=>array_column($this->internalSelections, 'alias')]);

        return $parser->parse($flatRows);
    }

}
