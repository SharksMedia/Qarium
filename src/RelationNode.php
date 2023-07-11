<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-10

class RelationNode
{
    /**
     * 2023-07-10
     * Can sometimes be the alias of the relation, but normally it is just the same as relationName
     * @var string|null
     */
    public ?string $name = null;

    /**
     * 2023-07-10
     * @var string
     */
    public ?string $relationName = null;

    /**
     * 2023-07-10
     * @var array
     */
    public array $modify = [];

    /**
     * 2023-07-10
     * @var bool
     */
    public bool $recursive = false;

    /**
     * 2023-07-10
     * @var int|null
     */
    public ?int $recursiveDepth = null;

    /**
     * 2023-07-10
     * @var bool
     */
    public bool $allRecursive = false;

    /**
     * 2023-07-10
     * @var array
     */
    public ?array $iChildNodes = [];
}
