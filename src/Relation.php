<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-06-16

class Relation
{
    public const TYPE_BELONGS_TO_ONE        = 'BELONGS_TO_ONE';
    public const TYPE_HAS_MANY              = 'HAS_MANY';
    public const TYPE_AS_ONE                = 'AS_ONE';
    public const TYPE_MANY_TO_MANY          = 'MANY_TO_MANY';
    public const TYPE_HAS_ONE_THROUGH       = 'HAS_ONE_THROUGH';

    /**
     * 2023-06-16
     * @var string
     */
    private string $name;

    /**
     * 2023-06-16
     * TYPE_BELONGS_TO_ONE | TYPE_HAS_MANY | TYPE_AS_ONE | TYPE_MANY_TO_MANY | TYPE_HAS_ONE_THROUGH
     * @var string
     */
    private string $type;

    /**
     * 2023-06-16
     * @var string
     */
    private string $owningModelClass;

    /**
     * 2023-06-16
     * @var string|null
     */
    private ?string $relatedModelClass;

    /**
     * 2023-06-16
     * @var array<string, mixed>
     */
    private array $ownerOptions;

    /**
     * 2023-06-16
     * @var array<string, mixed>
     */
    private array $relatedOptions;

    /**
     * 2023-06-16
     * @param string $relationName
     * @param string $owningModelClass
     */
    public function __construct(string $relationName, string $owningModelClass)
    {
        $this->name = $relationName;
        $this->owningModelClass = $owningModelClass;
    }

    /**
     * 2023-06-16
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 2023-06-16
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
