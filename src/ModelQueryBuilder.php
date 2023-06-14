<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

// require '../vendor/sharksmedia/query-builder/src/QueryBuilder.php';

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Statement\Columns;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;

class ModelQueryBuilder extends QueryBuilder
{
    /**
     * 2023-06-12
     * @var Model
     */
    private string $modelClass;

    /**
     * 2023-06-12
     * @var array<string, Model::class>
     */
    private array $graphModelClasses = [];

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

        // $result = ($this->getSelectMethod() === Columns::TYPE_FIRST)
        //     ? $statement->fetchObject($this->modelClass)
        //     : $statement->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);

        // $results = $statement->fetchAll(\PDO::FETCH_NUM);

        $iModels = [];
        while($result = $statement->fetch(\PDO::FETCH_NUM))
        {
            // NOTE: There might be a bug if a join graph does not have any data

            $tablesData = [];
            foreach($result as $index=>$value)
            {
                $columnInfo = $statement->getColumnMeta($index);
                $tablesData[$columnInfo['table']][$columnInfo['name']] = $value;
            }

            $modelClass = $this->modelClass;

            $data = $tablesData[$modelClass::getTableName()];

            foreach($this->graphModelClasses as $propName=>$modelClass)
            {
                $graphData = $modelClass::create($tablesData[$modelClass::getTableName()]);
                $data[$propName] = $graphData;
            }

            $iMainModel = new $modelClass($tablesData[$this->modelClass::getTableName()]); //  $this->modelClass::create($tablesData[$this->modelClass::getTableName()]);

            // psudeo code: if($this->fetchGenerated) yield $iMainModel;

            $iModels[] = $iMainModel;
        }

        $statement->closeCursor();

        if($this->getSelectMethod() === Columns::TYPE_FIRST) return array_shift($iModels);

        return $iModels;
    }

}

