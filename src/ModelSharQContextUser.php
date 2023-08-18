<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

// 2023-07-11

class ModelSharQContextUser
{
    /**
     * 2023-07-11
     * @var ModelSharQOperationSupport
     */
    private ModelSharQOperationSupport $iBuilder;

    /**
     * 2023-08-02
     * @var SharQ|null
     */
    private $iSharQ;

    /**
     * 2023-08-02
     * @var ModelSharQContextUser|null
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

    public function getInternalData()
    {
        return $this->internalData;
    }

    public function __construct(ModelSharQOperationSupport $iBuilder)
    {
        $this->iBuilder = $iBuilder;
    }

    public function getTransaction()
    {
        return $this->iBuilder->getSharQ();
    }

    public function newFromObject(ModelSharQOperationSupport $iBuilder, object $obj)
    {
        $context = new static($iBuilder);

        foreach($obj as $key=>$value)
        {
            $context->{$key} = $value;
        }

        return $context;
    }

    public function newMerge(ModelSharQOperationSupport $iBuilder, $obj)
    {
        $context = new static($iBuilder);

        foreach($this as $key=>$value)
        {
            $context->{$key} = $value;
        }

        if($obj instanceof ModelSharQContextUser) $obj = $obj->getInternalData();

        foreach($obj as $key=>$value)
        {
            $context->{$key} = $value;
        }

        return $context;
    }

    

}
