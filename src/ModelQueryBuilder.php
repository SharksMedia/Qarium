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

    /**
     * 2023-06-12
     * @var array<string, object>
     */
    private array $appliedRelations = [];

    public function __construct(string $modelClass, Client $iClient, string $schema)
    {// 2023-06-12
        if(!is_subclass_of($modelClass, Model::class)) throw new \Exception('Model class must be an instance of Model.');

        $this->modelClass = $modelClass;

        parent::__construct($iClient, $schema);

        // $this->column($modelClass::getTableName().'.*');
        $this->column(self::createAliases($modelClass::getTableName()));
    }

    /**
     * 2023-06-12
     * Finds a model by its ID(s).
     * @param string|int|Raw|array<string, string|int|Raw> $value
     * @return QueryBuilder
     */
    public function findByID($value): self
    {
        // FIXME: You cannot use limit 1 if the model has graphs joined
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

        $modelClass = $this->modelClass;

        return $this->where(self::tablePrefixColumnName($tableIDs[0], $modelClass::getTableName()), $value)->first();
    }

    public static function debugGetTableDefition(string $tableName): array
    {
        $definitions =
        [
            'Persons'=>
            [
		        [
			        "Field"=>"personID",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>"auto_increment"
		        ],
		        [
			        "Field"=>"name",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"parentID",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ]
            ]
        ];

        return $definitions[$tableName];
    }

    private static function createAliases(string $tableName, ?string $relationName=null, ?array $fields=null): array
    {// 2023-06-15
        $fields = $fields ?? array_column(self::debugGetTableDefition($tableName), 'Field');

        $aliasses = [];
        foreach($fields as $field)
        {
            $alias = is_null($relationName)
                ? $field
                : $relationName.':'.$field;

            $table = is_null($relationName)
                ? $tableName
                : $relationName;

            $aliasses[$alias] = $table.'.'.$field;
        }

        return $aliasses;
    }

    private static function tablePrefixColumnName(string $column, string $table)
    {// 2023-06-15
        $parts = explode('.', $column);

        if(count($parts) === 1) return $table.'.'.$column;

        return $table.'.'.$parts[1];
    }

    private static function getTableName(string $column): ?string
    {
        $parts = explode('.', $column);

        if(count($parts) === 1) return null;

        return $parts[0];
    }

    private static function normalizeRelation(string $relationName, array $relation, array $options=[]): object
    {
        $normalizedRelation = (object)
        [
            'name'=>$relationName,
            'alias'=>$options['alias'] ?? null,
            'relation'=>$relation['relation'],
            'modelClass'=>$relation['modelClass'],
            'join'=>(object)
            [
                'from'=>$relation['join']['from'],
                'through'=>(object)
                [
                    'from'=>$relation['join']['through']['from'] ?? null,
                    'to'=>$relation['join']['through']['to'] ?? null,
                    'extras'=>$relation['join']['through']['extras'] ?? [],
                ],
                'to'=>$relation['join']['to']
            ],
            'joinOperation'=>$options['joinOperation'] ?? 'leftJoin',
        ];

        return $normalizedRelation;
    }

    /**
     * 2023-06-15
     * @param string|Raw $relationName
     * @param array<string, string|array<string>> $options
     * @return QueryBuilder
     *
     * minimize         boolean     If true the aliases of the joined tables and columns created by withGraphJoined are minimized. This is sometimes needed because of identifier length limitations of some database engines. objection throws an exception when a query exceeds the length limit. You need to use this only in those cases.
     * separator        string      Separator between relations in nested withGraphJoined query. Defaults to :. Dot (.) cannot be used at the moment because of the way knex parses the identifiers.
     * aliases          Object      Aliases for relations in a withGraphJoined query. Defaults to an empty object.
     * joinOperation    string      Which join type to use ['leftJoin', 'innerJoin', 'rightJoin', ...] or any other knex join method name. Defaults to leftJoin.
     * maxBatchSize     integer     For how many parents should a relation be fetched using a single query at a time. If you set this to 1 then a separate query is used for each parent to fetch a relation. For example if you want to fetch pets for 5 persons, you get five queries (one for each person). Setting this to 1 will allow you to use stuff like limit and aggregate functions in modifyGraph and other graph modifiers. This can be used to replace the naiveEager objection 1.x had.
     */
    public function withGraphJoined($relationName, array $options=[]): self
    {// 2023-06-15
        $relations = call_user_func([$this->modelClass, 'getRelationMappings']);

        $relation = $relations[$relationName] ?? null;

        if($relation === null) throw new \Exception('Relation "'.$relationName.'" was not found.');

        $alias = $options['aliases'][$relationName] ?? $relationName;

        $normalizedRelation = self::normalizeRelation($relationName, $relation, $options);

        $this->appliedRelations[$alias] = $normalizedRelation;

        $relationType = $relation['relation'];
        // $modelClass = $relation['modelClass'];
        // $tableName = $modelClass::getTableName();
        // $from = self::tablePrefixColumnName($relation['join']['from'], $tableName);
        // $to = self::tablePrefixColumnName($relation['join']['to'], $relationName);
        //
        if($relationType === Model::BELONGS_TO_ONE_RELATION)
        {
            return $this->withGraphJoinedBelongsToOne($alias, $normalizedRelation);
        }
        else if($relationType === Model::HAS_MANY_RELATION)
        {
            return $this->withGraphJoinedHasMany($alias, $normalizedRelation);
        }
        else if($relationType === Model::AS_ONE_RELATION)
        {
            return $this->withGraphJoinedAsOne($alias, $normalizedRelation);
        }
        else if($relationType === Model::MANY_TO_MANY_RELATION)
        {
            return $this->withGraphJoinedManyToMany($alias, $normalizedRelation);
        }
        else if($relationType === Model::HAS_ONE_THROUGH_RELATION)
        {
            return $this->withGraphJoinedOneThroughRelation($alias, $normalizedRelation);
        }
        else
        {
            throw new \Exception('Relation "'.$relationType.'" is not supported.');
        }
    }

    /**
     * 2023-06-15
     * @param string $relationName
     * @param string|Raw $tableName
     * @param string|Raw $from
     * @param string|Raw $to
     * @return QueryBuilder
     */
    private function withGraphJoinedBelongsToOne(string $relationName, object $normalizedRelation): self
    {
        $toTableName = $normalizedRelation->modelClass::getTableName();

        $this->leftJoin($toTableName.' AS '.$relationName, $normalizedRelation->from, $normalizedRelation->to);
        
        $aliasses = self::createAliases($tableName, $relationName);

        $this->select($aliasses);

        return $this;
    }

    /**
     * 2023-06-15
     * @param string $relationName
     * @param string|Raw $tableName
     * @param string|Raw $from
     * @param string|Raw $to
     * @return QueryBuilder
     */
    private function withGraphJoinedHasMany(string $relationName, object $normalizedRelation): self
    {
        $toTableName = $normalizedRelation->modelClass::getTableName();
        $from = self::tablePrefixColumnName($normalizedRelation->join->from, $this->modelClass::getTableName());
        $to = self::tablePrefixColumnName($normalizedRelation->join->to, $relationName);

        $joinOperation = $normalizedRelation->joinOperation;

        $this->{$joinOperation}($toTableName.' AS '.$relationName, $to, $from);
        
        $aliasses = self::createAliases($toTableName, $relationName);

        $this->select($aliasses);

        return $this;
    }

    /**
     * 2023-06-15
     * @param string $relationName
     * @param string|Raw $tableName
     * @param string|Raw $from
     * @param string|Raw $to
     * @return QueryBuilder
     */
    private function withGraphJoinedAsOne($normalizedRelation): self
    {
        $toTableName = $normalizedRelation->modelClass::getTableName();

        $this->leftJoin($toTableName.' AS '.$relationName, $normalizedRelation->from, $normalizedRelation->to);
        
        $aliasses = self::createAliases($tableName, $relationName);

        $this->select($aliasses);

        return $this;
    }

    /**
     * 2023-06-15
     * @param string $relationName
     * @param string|Raw $tableName
     * @param string|Raw $from
     * @param string|Raw $thoughFrom
     * @param string|Raw $throughTo
     * @param string|Raw $to
     * @return QueryBuilder
     */
    private function withGraphJoinedManyToMany(string $relationName, $normalizedRelation): self
    {
        $fromTableName = $this->modelClass::getTableName();
        $toTableName = $normalizedRelation->modelClass::getTableName();

        $throughFromTableName = self::getTableName($normalizedRelation->through->from);
        $throughToTableName = self::getTableName($normalizedRelation->through->to);

        $joinOperation = $normalizedRelation->joinOperation;

        $joinTable = $throughFromTableName.' AS '.$relationName.'_join';
        $joinFrom = self::tablePrefixColumnName($normalizedRelation->from, $fromTableName);
        $joinTo = self::tablePrefixColumnName($normalizedRelation->through->from, $throughFromTableName);

        // Join the through table
        $this->{$joinOperation}($joinTable, $joinTo, $joinFrom);

        $table = $toTableName.' AS '.$relationName;
        $from = self::tablePrefixColumnName($normalizedRelation->through->to, $throughToTableName);
        $to = self::tablePrefixColumnName($normalizedRelation->to, $toTableName);

        // Join the to table
        $this->{$joinOperation}($table, $to, $from);
        
        $aliasses = self::createAliases($toTableName, $relationName);
        $aliasses = array_merge(self::createAliases($throughFromTableName, $relationName, $normalizedRelation->through->extras), $aliasses);

        $this->select($aliasses);

        return $this;
    }

    /**
     * 2023-06-15
     * @param string $relationName
     * @param string|Raw $tableName
     * @param string|Raw $from
     * @param string|Raw $thoughFrom
     * @param string|Raw $throughTo
     * @param string|Raw $to
     * @return QueryBuilder
     */
    private function withGraphJoinedOneThroughRelation($normalizedRelation): self
    {

    }

    private function createModelFromResult(array $result)
    {
        $data = [];
        foreach($result as $column=>$value)
        {
            $columnParts = explode(':', $column);

            if(count($columnParts) === 1)
            {
                $table = $this->modelClass::getTableName();

                $data[$table][$column] = $value;

                continue;
            }

            $table = $columnParts[0];
            $column = $columnParts[1];

            $data[$table][$column] = $value;
        }

        $tableName = $this->modelClass::getTableName();

        /** @var \Sharksmedia\Objection\Model $iModel */
        $iModel = $this->modelClass::create($data[$tableName]);
        unset($data[$tableName]);

        foreach($data as $table=>$columns)
        {
            $appliedRelation = $this->appliedRelations[$table] ?? null;

            codecept_debug($table);
            codecept_debug($columns);

            $iRelatedModel = $appliedRelation->modelClass::create($columns);

            if($iRelatedModel === null) continue;

            $iModel->{$appliedRelation->name} = $iRelatedModel;
        }

        return $iModel;
    }

    public function normalizeResults(array $results)
    {
        $modelIDsMap = $this->modelClass::getTableIDsMap();
        $datas = [];
        // $data = [];
        foreach($results as $i=>$result)
        {
            $resultIDs = array_intersect_key($result, $modelIDsMap);
            $key = implode(':', $resultIDs);
            $data = $datas[$key] ?? [];
            foreach($result as $column=>$value)
            {
                $columnParts = explode(':', $column);
                $column = array_pop($columnParts);
                $relations = $columnParts;

                $ref = &$data;
                foreach($relations as $relation)
                {
                    $ref[$relation] = $ref[$relation] ?? [];
                    $ref = &$ref[$relation][$i];
                }

                $ref[$column] = $value;
            }

            $datas[$key] = $data;
        }

        return $datas;
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

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $normalizeResults = $this->normalizeResults($results);

        $iModels = [];
        foreach($normalizeResults as $result)
        {
            $iModels[] = $this->modelClass::create($result, $this->appliedRelations);
        }

        return $iModels;

        // $result = ($this->getSelectMethod() === Columns::TYPE_FIRST)
        //     ? $statement->fetchObject($this->modelClass)
        //     : $statement->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);

        // $results = $statement->fetchAll(\PDO::FETCH_NUM);
        /*

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
        */
    }

}

