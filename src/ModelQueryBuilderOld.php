<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

// require '../vendor/sharksmedia/query-builder/src/QueryBuilder.php';

use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Query;
use Sharksmedia\QueryBuilder\Statement\Columns;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\QueryCompiler;

class ModelQueryBuilder extends QueryBuilder
{
    /**
     * 2023-06-12
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * 2023-06-12
     * @var array<string, object>
     */
    protected array $iRelations = [];

    /**
     * 2023-06-12
     * @var array<string, array>
     */
    protected array $allowGraph = [];

    /**
     * 2023-06-12
     * @param class-string<Model> $modelClass
     * @param Client $iClient
     * @param string $schema
     */
    public function __construct(string $modelClass, Client $iClient, string $schema)
    {// 2023-06-12
        if(!is_subclass_of($modelClass, Model::class)) throw new \Exception('Model class must be an instance of Model.');

        $this->modelClass = $modelClass;

        parent::__construct($iClient, $schema);

        // FIXME: Only create aliases if needed. ie. When at relation is added
        // $this->column();
    }

    /**
     * 2023-06-12
     * @return string-class<Model>
     */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * 2023-06-12
     * Finds a model by its ID(s).
     * @param string|int|Raw|array<string, string|int|Raw> $value
     * @return ModelQueryBuilder
     */
    public function findByID($value): self
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

        $modelClass = $this->modelClass;

        return $this->where(self::tablePrefixColumnName($tableIDs[0], $modelClass::getTableName()), $value)->first();
    }

    /**
     * Overrides the first function from QueryBuilder, as limiting to 1 row is not possible when using graphs.
     * @param array<int, string|Raw|QueryBuilder> $columns One or more values
     * @return ModelQueryBuilder
     */
    public function first(...$columns): ModelQueryBuilder
    {// 2023-05-15
        $this->method = self::METHOD_FIRST;

        return $this;
    }

    /**
     * @param int|Raw|QueryBuilder $value
     * @param array<int,mixed> $options
     * @return ModelQueryBuilder
     */
    public function limit($value, ...$options): ModelQueryBuilder
    {// 2023-05-26
        throw new \BadMethodCallException('Method "limit" is not supported when using graphs.');
    }

    public function clearAllowGraph(): ModelQueryBuilder
    {
        $this->allowGraph = [];

        return $this;
    }

    public function clearWithGraph(): ModelQueryBuilder
    {
        $this->iRelations = [];

        return $this;
    }

    public function debugGetTableDefition(string $tableName): array
    {
        // TODO: Create a function on the compiler to do this

        static $cache = [];

        if(isset($cache[$tableName])) return $cache[$tableName];

        $method = '';
        $options = [];
        $timeout = 0;
        $cancelOnTimeout = false;
        $bindings = [];
        $UUID = 'describe_' . $tableName;
        $iQuery = new Query($method, $options, $timeout, $cancelOnTimeout, $bindings, $UUID);

        // WARN: This is not safe, becuase we are not escaping the table name
        $iQuery->setSQL('SHOW COLUMNS FROM ' . $tableName);

        $iClient = $this->getClient();

        if(!$iClient->isInitialized()) $iClient->initializeDriver();

        $statement = $iClient->query($iQuery);

        $cache[$tableName] = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $cache[$tableName];
    }

    private function createAliases(string $tableName, ?string $relationName=null, ?array $fields=null, ?string $tableAlias=null): array
    {// 2023-06-15
        $fields = $fields ?? array_column($this->debugGetTableDefition($tableName), 'Field');

        $aliasses = [];
        foreach($fields as $field)
        {
            $alias = is_null($relationName)
                ? $field
                : $relationName.':'.$field;

            $table = $tableAlias ?? is_null($relationName)
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

    /**
     * 2023-06-19
     * @return array<int, Relation>
     */
    private function createRelationsFromGraph(array $graph, string $parentModelClass, array $options=[]): array
    {
        $relations = call_user_func([$parentModelClass, 'getRelationMappings']);

        $iRelations = [];

        foreach($graph as $relationName=>$childRelations)
        {
            $relation = $relations[$relationName] ?? null;

            if($relation === null) throw new \Exception('Relation "'.$relationName.'" was not found.');

            $iRelation = new Relation($relationName, $parentModelClass, $relation['modelClass']);

            $iRelation->setOptions($options);

            $iRelations[$relationName] = $iRelation;

            if($childRelations)
            {
                foreach(self::createRelationsFromGraph($childRelations, $relation['modelClass'], $options) as $iChildRelation)
                {
                    $iRelations[$relationName]->addChildRelation($iChildRelation);
                }
            }
        }

        return $iRelations;
    }

    /**
     * 2023-06-19
     * @return array|null
     */
    public function parseRelationQuery(string $case): ?array
    {
        // $regex = '/(\w+)\.?(?<R>\[(?:[^\[\]]+|(?&R))*\])?/';
        $regex = '/(\w+)\.?(\[(?:[^\[\]]+|(?R))*\]|(?R))?/';
        
        preg_match_all($regex, $case, $m);
        
        $topLevelGroups = array_shift($m);
        $topLevelNames = array_shift($m);
        $recursiveGroups = array_shift($m);
        
        $groupsToProcess = (count($topLevelGroups) > 1)
            ? $topLevelGroups
            : $recursiveGroups;
        
        if(count($groupsToProcess) === 0) return null;
        
        $isArray = ($case[0] === '[');
        
        $results = [];
        foreach(array_combine($topLevelNames, $groupsToProcess) as $name=>$caseToProcess)
        {
            $childResults = self::parseRelationQuery($caseToProcess);

            if(!$isArray || $childResults === null)
            {
                $results[$name] = $childResults;
            }
            else
            {
                $results = array_merge($childResults, $results);
            }
        }
        
        return $results;
    }

    /**
     * 2023-06-15
     * minimize         boolean     If true the aliases of the joined tables and columns created by withGraphJoined are minimized. This is sometimes needed because of identifier length limitations of some database engines. objection throws an exception when a query exceeds the length limit. You need to use this only in those cases.
     * separator        string      Separator between relations in nested withGraphJoined query. Defaults to :. Dot (.) cannot be used at the moment because of the way knex parses the identifiers.
     * aliases          Object      Aliases for relations in a withGraphJoined query. Defaults to an empty object.
     * joinOperation    string      Which join type to use ['leftJoin', 'innerJoin', 'rightJoin', ...] or any other knex join method name. Defaults to leftJoin.
     * maxBatchSize     integer     For how many parents should a relation be fetched using a single query at a time. If you set this to 1 then a separate query is used for each parent to fetch a relation. For example if you want to fetch pets for 5 persons, you get five queries (one for each person). Setting this to 1 will allow you to use stuff like limit and aggregate functions in modifyGraph and other graph modifiers. This can be used to replace the naiveEager objection 1.x had.
     *
     * @param string|Raw $relationName
     * @param array<string, string|array<string>> $options
     * @return ModelQueryBuilder
     */
    public function withGraphJoined(string $relationExpression, array $options=[]): self
    {// 2023-06-15
        $relationsGraph = self::parseRelationQuery($relationExpression);

        if($this->allowGraph) self::_validateGraph($relationsGraph, $this->allowGraph);

        $iRelations = self::createRelationsFromGraph($relationsGraph, $this->modelClass, $options);

        $this->iRelations = array_merge($iRelations, $this->iRelations);

        return $this;
    }

    /**
     * 2023-06-23
     * @param array<string, array> $graph
     * @param array<string, array> $allowGraph
     * @return void
     */
    protected static function _validateGraph(array $graph, array $allowGraph): void
    {
        foreach($graph as $relationName=>$childRelations)
        {
            if(!array_key_exists($relationName, $allowGraph)) throw new \Exception('Relation "'.$relationName.'" is not allowed.');
            
            if($childRelations)
            {
                self::_validateGraph($childRelations, $allowGraph[$relationName]);
            }
        }
    }

    /**
     * 2023-06-23
     * @param string $relationExpression
     * @return ModelQueryBuilder
     */
    public function allowGraph(string $relationExpression): self
    {
        $this->allowGraph = array_merge($this->allowGraph, self::parseRelationQuery($relationExpression));

        return $this;
    }

    /**
     * 2023-06-22
     * @param array<string, array> $targetsGraph
     * @param callable $callback
     * @param array<int, Relation> $iRelations
     * @return void
     */
    private function _modifyGraph(array $targetsGraph, callable $callback, array $iRelations): void
    {
        foreach($targetsGraph as $relationName=>$graph)
        {
            $iRelation = $iRelations[$relationName] ?? null;

            if($iRelation === null) throw new \Exception('Relation "'.$relationName.'" was not found.');

            if(is_array($graph))
            {
                $this->_modifyGraph($graph, $callback, $iRelation->getChildRelations());

                continue;
            }

            $relationModelClass = $iRelation->getRelatedModelClass();

            $iModelQueryBuilder = $relationModelClass::query();
            $iModelQueryBuildRef = &$iModelQueryBuilder;

            $callback($iModelQueryBuildRef);

            $iRelation->setTableFromQueryBuilder($iModelQueryBuilder);
        }

    }

    /**
     * 2023-06-22
     * @return ModelQueryBuilder
     */
    public function modifyGraph(string $target, callable $callback): self
    {// 2023-06-22
        $targetsGraph = self::parseRelationQuery($target);

        $this->_modifyGraph($targetsGraph, $callback, $this->iRelations);

        return $this;
    }

    private function withRelationJoined(Relation $iRelation, ?string $prefix=null)
    {
        $relationType = $iRelation->getType();

        if($relationType === Model::BELONGS_TO_ONE_RELATION)
        {
            return $this->withGraphJoinedBelongsToOne($iRelation, $prefix);
        }
        else if($relationType === Model::HAS_MANY_RELATION)
        {
            return $this->withGraphJoinedHasMany($iRelation, $prefix);
        }
        else if($relationType === Model::HAS_ONE_RELATION)
        {
            return $this->withGraphJoinedHasOne($iRelation, $prefix);
        }
        else if($relationType === Model::MANY_TO_MANY_RELATION)
        {
            return $this->withGraphJoinedManyToMany($iRelation, $prefix);
        }
        else if($relationType === Model::HAS_ONE_THROUGH_RELATION)
        {
            return $this->withGraphJoinedOneThroughRelation($iRelation, $prefix);
        }
        else
        {
            throw new \Exception('Relation "'.$relationType.'" is not supported.');
        }
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedBelongsToOne(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $tableAlias = $iRelation->getJoinTableAlias($aliasPrefix);

        $joinOperation = $iRelation->getJoinOperation();

        $this->{$joinOperation}($iRelation->getJoinTable($aliasPrefix), $iRelation->getToColumn($tableAlias), $iRelation->getFromColumn());

        $aliasses = $this->createAliases($iRelation->getRelatedModelClass()::getTableName(), $tableAlias);

        $this->select($aliasses);

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $tableAlias);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedHasMany(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $tableAlias = $iRelation->getJoinTableAlias($aliasPrefix);

        $joinOperation = $iRelation->getJoinOperation();

        $this->{$joinOperation}($iRelation->getJoinTable($aliasPrefix), $iRelation->getToColumn($tableAlias), $iRelation->getFromColumn());

        $aliasses = $this->createAliases($iRelation->getRelatedModelClass()::getTableName(), $tableAlias);

        $this->select($aliasses);

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $tableAlias);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedHasOne(Relation $iRelation, ?string $aliasPrefix): self
    {
        $tableAlias = $iRelation->getJoinTableAlias($aliasPrefix);

        $joinOperation = $iRelation->getJoinOperation();

        $this->{$joinOperation}($iRelation->getJoinTable($aliasPrefix), $iRelation->getToColumn($tableAlias), $iRelation->getFromColumn());

        $aliasses = $this->createAliases($iRelation->getRelatedModelClass()::getTableName(), $tableAlias);

        $this->select($aliasses);

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $tableAlias);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedManyToMany(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $throughTableAlias = $iRelation->getJoinThroughTableAlias($aliasPrefix);
        $tableAlias = $iRelation->getJoinTableAlias($aliasPrefix);

        $joinOperation = $iRelation->getJoinOperation();

        $this->{$joinOperation}($iRelation->getJoinThroughTable($aliasPrefix), $iRelation->getThroughFromColumn($throughTableAlias), $iRelation->getFromColumn());
        $this->{$joinOperation}($iRelation->getJoinTable($aliasPrefix), $iRelation->getToColumn($tableAlias), $iRelation->getThroughToColumn($throughTableAlias));

        $aliasses = $this->createAliases($iRelation->getRelatedModelClass()::getTableName(), $tableAlias);
        $extraAliasses = $this->createAliases($throughTableAlias, $tableAlias, $iRelation->getThroughExtras(), $throughTableAlias);

        $this->select(array_merge($aliasses, $extraAliasses));

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $tableAlias);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedOneThroughRelation(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $throughTableAlias = $iRelation->getJoinThroughTableAlias($aliasPrefix);
        $tableAlias = $iRelation->getJoinTableAlias($aliasPrefix);

        $joinOperation = $iRelation->getJoinOperation();

        $this->{$joinOperation}($iRelation->getJoinThroughTable($aliasPrefix), $iRelation->getThroughFromColumn($throughTableAlias), $iRelation->getFromColumn());
        $this->{$joinOperation}($iRelation->getJoinTable($aliasPrefix), $iRelation->getToColumn($tableAlias), $iRelation->getThroughToColumn($throughTableAlias));

        $aliasses = $this->createAliases($iRelation->getRelatedModelClass()::getTableName(), $tableAlias);
        $extraAliasses = $this->createAliases($throughTableAlias, $tableAlias, $iRelation->getThroughExtras(), $throughTableAlias);

        $this->select(array_merge($aliasses, $extraAliasses));

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $tableAlias);
        }

        return $this;
    }

    /**
     * @param array<int, string|Raw|QueryBuilder> $args [values, returning, options]
     * @return QueryBuilder
     */
    public function insert(...$args): QueryBuilder
    {// 2023-07-03
        $values = $args[0] ?? null;
        $returning = $args[1] ?? null;
        $options = $args[2] ?? null;

        if(is_array($values[0] ?? null)) throw new \BadFunctionCallException('Inserting multiple rows is not supported.');

        return parent::insert(...$args);
    }

    /**
     * 2023-07-03
     * @return ModelQueryBuilder
     */
    public function insertAndFetch(...$args)
    {
        $values = $args[0] ?? null;
        $returning = $args[1] ?? null;
        $options = $args[2] ?? null;

        if(is_array($values[0] ?? null)) throw new \BadFunctionCallException('Inserting multiple rows is not supported.');

        return parent::insert(...$args);
    }

    /**
     * 2023-06-21
     * Generated by ChatGPT 4
     * @param array $results
     * @return array
     */
    private static function _flattenResults(array &$results): array
    {
        // Step 1: Flatten the data structure.
        $flattened = [];
        foreach ($results as $item) {
            $temp = [];
            foreach ($item as $key => $value) {
                $keys = explode(':', $key);
                $current = &$temp;
                foreach ($keys as $innerKey) {
                    if (!isset($current[$innerKey])) {
                        $current[$innerKey] = [];
                    }
                    $current = &$current[$innerKey];
                }
                $current = $value;
            }
            $flattened[] = $temp;
        }
        
        return $flattened;
    }

    public static function mergeResult($result, $modelIDsMap, $iRelations, &$output=[])
    {
        $key = implode(':', array_intersect_key($result, $modelIDsMap));

        if($key === '') return;

        $output[$key] = $output[$key] ?? [];

        $data = &$output[$key];

        foreach($result as $column=>$value)
        {
            $iRelation = $iRelations[$column] ?? null;

            if($iRelation !== null)
            {
                $data[$iRelation->getName()] = $data[$iRelation->getName()] ?? [];
                self::mergeResult($value, $iRelation->getRelatedModelClass()::getTableIDsMap(), $iRelation->getChildRelations(), $data[$iRelation->getName()]);
            }
            else
            {
                $data[$column] = $value;
            }
        }
    }

    /**
     * 2023-06-19
     * @param array $results
     * @return Model[]|Model
     */
    private function createGraphFromResults(array $results)
    {
        $modelIDsMap = $this->modelClass::getTableIDsMap();
        $iRelations = $this->iRelations;

        $resultsFlat = self::_flattenResults($results);

        $output = [];
        foreach($resultsFlat as $result)
        {
            self::mergeResult($result, $modelIDsMap, $iRelations, $output);
        }

        return $output;
    }

    public function toSQL(): Query
    {// 2023-06-12
        $iModelQueryBuilder = clone $this;

        $iModelQueryBuilder->preCompile();

        $iQueryCompiler = new QueryCompiler($this->getClient(), $iModelQueryBuilder, []);

        return $iQueryCompiler->toSQL();
    }

    /**
     * 2023-06-22
     * This method is used to pre-compile the query.
     * Precompilation is needed because we can use modifyGraph to change how relations are joined.
     * @return Model[]|Model
     */
    public function preCompile(): void
    {
        // NOTE: Consider moving this function to the QueryCompiler class.
        // FIXME: Allow for custom selects
        if(count($this->iRelations) !== 0)
        {
            $this->column($this->createAliases($this->modelClass::getTableName()));
        }
        
        // Walking backwards through array, so the order in which relations has been added, matches
        $iRelation = end($this->iRelations);
        do
        {
            if($iRelation === false) continue;

            $this->withRelationJoined($iRelation);
        }
        while($iRelation = prev($this->iRelations));
    }

    private function createModelsFromResultsGraph(array $resultsGraph): array
    {
        $iModels = [];
        foreach($resultsGraph as $result)
        {
            $iModels[] = $this->modelClass::create($result, $this->iRelations);
        }

        return $iModels;
    }

    /**
     * 2023-06-12
     * @return Model[]|Model|null
     */
    public function run()
    {// 2023-06-12
        $this->preCompile();

        $iQueryCompiler = new QueryCompiler($this->getClient(), $this, []);

        $iQuery = $iQueryCompiler->toSQL();

        $statement = $this->getClient()->query($iQuery);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if($this->isSelectQuery())
        {
            $resultsGraph = $this->createGraphFromResults($results);

            $iModels = $this->createModelsFromResultsGraph($resultsGraph);

            if($this->getMethod() === self::METHOD_FIRST) return $iModels[0] ?? null;

            return $iModels;
        }
        else if($this->getMethod() === self::METHOD_INSERT)
        {
            // TODO: Fetch last inserted ID and create model with that plus the data provided in the insert. We have a need for a flag to say if we should return the first, or the array

            return null;
        }
        else if($this->getMethod() === self::METHOD_UPDATE)
        {
            return $this->modelClass::create($results[0], $this->iRelations);
        }
        else if($this->getMethod() === self::METHOD_DELETE)
        {
            return $this->modelClass::create($results[0], $this->iRelations);
        }

    }

}

