<?php

/**
 * 2023-07-03
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\QueryBuilder\Client;

// require '../vendor/sharksmedia/query-builder/src/QueryBuilder.php';

class ModelRelatedQueryBuilder extends ModelQueryBuilder
{
    /**
     * 2023-07-03
     * @var class-string<Model>
     */
    private string $parentModelClass;

    /**
     * 2023-07-03
     * @param class-string<Model> $modelClass
     * @param Client $iClient
     * @param string $schema
     */
    public function __construct(string $modelClass, string $parentModelClass, Client $iClient, string $schema)
    {// 2023-06-12
        parent::__construct($modelClass, $iClient, $schema);

        if(!is_subclass_of($parentModelClass, Model::class)) throw new \Exception('Model class must be an instance of Model.');

        $relationsGraph = self::parseRelationQuery($relationExpression);

        if($this->allowGraph) self::_validateGraph($relationsGraph, $this->allowGraph);

        $this->parentModelClass = $parentModelClass;
    }
}
