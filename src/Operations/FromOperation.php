<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

class FromOperation extends ModelQueryBuilderOperation
{
    public const ALIAS_REGEX = '/\s+as\s+/i';

    /**
     * 2023-07-11
     * @var string|null
     */

    private ?string $table = null;
    /**
     * 2023-07-11
     * @var string|null
     */
    private ?string $alias = null;

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }


}
