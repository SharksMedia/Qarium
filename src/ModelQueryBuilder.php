<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

// require '../vendor/sharksmedia/query-builder/src/QueryBuilder.php';

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;

class ModelQueryBuilder extends QueryBuilder
{
    private string $modelClass;

    public function __construct(string $modelClass, Client $iClient, string $schema)
    {// 2023-06-12
        if(!is_subclass_of($modelClass, Model::class)) throw new \Exception('Model class must be an instance of Model.');

        $this->modelClass = $modelClass;

        parent::__construct($iClient, $schema);
    }

    /**
     * 2023-06-12
     * Finds a model by its ID(s).
     * @param string|int|Raw|array<string, string|int|Raw> $value
     * @return QueryBuilder
     */
    public function findByID($value): QueryBuilder
    {
        $tableIDs = call_user_func([$this->modelClass, 'getTableIDs']);

        if(is_array($value))
        {
            foreach($value as $columnName => $columnValue)
            {
                $this->where($columnName, $columnValue);
            }

            return $this->first();
        }

        if(count($tableIDs) > 1) throw new \Exception('Table has more than one ID column, please use use an array value.');

        return $this->where($tableIDs[0], $value)->first();
    }

    /**
     * 2023-06-12
     * @return Model[]|Model
     */
    public function run()
    {// 2023-06-12
        $iQueryCompiler = new QueryCompiler($this->getClient(), $this, []);

        $iQuery = $iQueryCompiler->toSQL();

        $statement = $this->getClient()->query($iQuery);

        $result = ($this->getSelectMethod() === self::METHOD_FIRST)
            ? $statement->fetchObject($this->modelClass)
            : $statement->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);

        $statement->closeCursor();

        if($result === false) return null;

        return $result;
    }

}

