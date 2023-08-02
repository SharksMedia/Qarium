<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

class ModelQueryBuilderContextBase
{
    /**
     * @var ModelQueryBuilderContextUser|null
     */
    public $userContext = null;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @return QueryBuilder|null
     */
    public $iQueryBuilder = null;

    /**
     * @return array|null
     */
    public $aliasMap = null;

    /**
     * @return array|null
     */
    public $tableMap = null;

    public function __construct(?ModelQueryBuilderOperationSupport $iBuilder=null)
    {
        if($iBuilder === null) return;

        $userContextClass = $iBuilder->getModelQueryBuilderUserContextClass();

        if($iBuilder !== null) $this->userContext = new $userContextClass($iBuilder);

        $this->options = $iBuilder->getInternalOptions();
    }

    public function getRunBeforeCallback(): callable
    {
        return function(){};
    }

    public function getRunAfterCallback(): callable
    {
        return function(){};
    }

    public function getOnBuildCallback(): callable
    {
        return function(){};
    }



}
