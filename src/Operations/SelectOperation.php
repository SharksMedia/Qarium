<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\Utilities;
use Sharksmedia\SharQ\SharQ;

class SelectOperation extends QariumToSharQConvertingOperation
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

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
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
     * @param ModelSharQ|ModelSharQOperationSupport $iBuilder
     * @param SharQ|Join|null $iSharQ
     * @return SharQ|Join|null
     */
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        if($iSharQ !== null && !($iSharQ instanceof SharQ) && !($iSharQ instanceof Join))  throw new \Exception('Invalid SharQ type: '.get_class($iSharQ));

        $arguments = $this->getArguments($iBuilder);

        return $iSharQ->{$this->name}(...$arguments);
    }


    // public function hasSelections(): bool
    // {
    //     return count($this->selections) > 0;
    // }

    public function getSelections(): array
    {
        return $this->selections;
    }

    public function hasSelections(): bool
    {
        return count($this->selections) > 0;
    }

    public function findSelection(ModelSharQOperationSupport $iBuilder, $selectionToFind): ?Selection
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
