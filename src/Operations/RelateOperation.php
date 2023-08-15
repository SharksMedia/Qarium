<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Relations\Relation;
use Sharksmedia\Objection\Relations\RelationOwner;

class RelateOperation extends ModelQueryBuilderOperation
{
    protected Relation $iRelation;
    protected RelationOwner $iOwner;

    protected $input;
    protected array $ids = [];

    public function __construct(string $name, Relation $iRelation, RelationOwner $iOwner, array $options=[])
    {
        parent::__construct($name, $options);

        $this->iRelation = $iRelation;
        $this->iOwner = $iOwner;

        $this->input = null;
        $this->ids = [];
    }
}
