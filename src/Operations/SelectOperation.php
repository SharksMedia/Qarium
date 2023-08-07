<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\Objection\Utilities;
use Sharksmedia\QueryBuilder\QueryBuilder;

class SelectOperation extends ObjectionToQueryBuilderConvertingOperation
{
    public const COUNT_REGEX = '/count/i';

    /**
     * @var array
     */
    private $selections;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);
        $this->selections = [];
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $selections = Utilities::array_flatten($arguments);

        if(count($selections) === 0 && preg_match(self::COUNT_REGEX, $this->name) !== 1) return false;
        
        $return = parent::onAdd($iBuilder, $selections);

        foreach($selections as $selection) {
            $iSelection = Selection::create($selection);

            if($iSelection !== null) $this->selections[] = $iSelection;
        }

        return $return;
    }

    /**
     * @param ModelQueryBuilder $iBuilder
     * @param QueryBuilder|Join|null $iQueryBuilder
     * @return QueryBuilder|Join|null
     */
    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, $iQueryBuilder)
    {
        if($iQueryBuilder !== null && !($iQueryBuilder instanceof QueryBuilder) && !($iQueryBuilder instanceof Join))  throw new \Exception('Invalid QueryBuilder type: '.get_class($iQueryBuilder));

        $arguments = $this->getArguments($iBuilder);

        return $iQueryBuilder->{$this->name}(...$arguments);
    }


    // public function hasSelections(): bool
    // {
    //     return count($this->selections) > 0;
    // }

    public function getSelections(): array
    {
        return $this->selections;
    }

    public function findSelection(ModelQueryBuilderOperationSupport $iBuilder, string $selectionToFind): ?Selection
    {
        $selectionInstanceToFind = Selection::create($selectionToFind);

        if($selectionInstanceToFind === null) return null;

        foreach($this->selections as $selection)
        {
            if(Selection::doesSelect($iBuilder, $selection, $selectionInstanceToFind)) return $selection;
        }

        return null;
    }

}
