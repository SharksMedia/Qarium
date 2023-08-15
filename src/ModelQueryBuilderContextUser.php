<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-11

class ModelQueryBuilderContextUser
{
    /**
     * 2023-07-11
     * @var ModelQueryBuilderOperationSupport
     */
    private ModelQueryBuilderOperationSupport $iBuilder;

    /**
     * 2023-08-02
     * @var QueryBuilder|null
     */
    private $iQueryBuilder;

    /**
     * 2023-08-02
     * @var ModelQueryBuilderContextUser|null
     */
    private $userContext;

    /**
     * 2023-08-02
     * @var array
     */
    private $options;

    /**
     * 2023-08-02
     * @var array|null
     */
    private $aliasMap;

    /**
     * 2023-08-02
     * @var array|null
     */
    private $tableMap;

    private array $internalData = [];

    public function __get($name)
    {
        return $this->internalData[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->internalData[$name] = $value;
    }

    public function __construct(ModelQueryBuilderOperationSupport $iBuilder)
    {
        $this->iBuilder = $iBuilder;
    }

    public function getTransaction()
    {
        return $this->iBuilder->getQueryBuilder();
    }

    public function newFromObject(ModelQueryBuilderOperationSupport $iBuilder, object $obj)
    {
        $context = new static($iBuilder);

        foreach($obj as $key=>$value)
        {
            $context->{$key} = $value;
        }

        return $context;
    }

    public function newMerge(ModelQueryBuilderOperationSupport $iBuilder, $obj)
    {
        $context = new static($iBuilder);

        foreach($this as $key=>$value)
        {
            $context->{$key} = $value;
        }

        foreach($obj as $key=>$value)
        {
            $context->{$key} = $value;
        }

        return $context;
    }

    

}
