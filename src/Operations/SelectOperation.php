<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\Utilities;

class SelectOperation extends ModelQueryBuilderOperation
{
    public const COUNT_REGEX = '/count/i';

    /**
     * @var array
     */
    private $selections;

    public function __construct(string $name, array $options)
    {
        parent::__construct($name, $options);
        $this->selections = [];
    }

    public function onAdd(ModelQueryBuilder $builder, array $arguments): bool
    {
        $selections = Utilities::array_flatten($arguments);

        if(count($selections) === 0 && preg_match(self::COUNT_REGEX, $this->name) !== 1) return false;
        
        $return = parent::onAdd($builder, $selections);

        foreach($selections as $selection) {
            $iSelection = Selection::create($selection);

            if($iSelection !== null) $this->selections[] = $iSelection;
        }

        return $return;
    }

}
