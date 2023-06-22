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
    private string $modelClass;

    /**
     * 2023-06-12
     * @var array<string, object>
     */
    private array $iRelations = [];

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
     * @return QueryBuilder
     */
    public function first(...$columns): QueryBuilder
    {// 2023-05-15
        $this->iSingle->columnMethod = Columns::TYPE_FIRST;

        return $this;
    }

    /**
     * @param int|Raw|QueryBuilder $value
     * @param array<int,mixed> $options
     * @return QueryBuilder
     */
    public function limit($value, ...$options): QueryBuilder
    {// 2023-05-26
        // TODO: Implement me!
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
            ],
            'Schools'=>
            [
		        [
			        "Field"=>"schoolID",
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
		        ]
            ],
            'categories'=>
            [
		        [
			        "Field"=>"categories_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>"auto_increment"
		        ],
		        [
			        "Field"=>"categories_image",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"parent_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"top_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"sort_order",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"group_order",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"date_added",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"last_modified",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"distributors_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"emarsys_field_names",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"groupName",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"showCustomFilters",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"outOfStockLast",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ]
            ],
            'products'=>
            [
		        [
			        "Field"=>"products_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>"auto_increment"
		        ],
		        [
			        "Field"=>"products_quantity",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_limit",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"min_purchase_quantity",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"purchase_quantity_divisor",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_model",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_date_added",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_last_modified",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_weight",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"dimensions",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_membership_discount_percentage",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_status",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_tax_class_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"manufacturers_id",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"manufacturers_sec_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"supplier_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"supplier_product_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"ean_number",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"primary_category_id",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"translated_se",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"translation_approved_se",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"translated_no",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"translation_approved_no",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"colli_size",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"customs_tariff_number",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"origin_country",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"discontinued",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"orderedAlone",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"secret",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"deleted",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"excludeFromNewsList",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"warehouseRemarks",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"dangerousGoods",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"isDrug",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"pharmacyProductID",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"requiresUDI",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"revisionAdministratorID",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ]
            ],
            'products_description'=>
            [
		        [
			        "Field"=>"products_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>"auto_increment"
		        ],
		        [
			        "Field"=>"language_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name_brand",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name_type",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name_variant",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name_quantity",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_name_unit",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_description",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_description_source",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"primaryContentSourceID",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"secondaryContentSourceID",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_manchet",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_metatag_description",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_metatag_keywords",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_title",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_h1",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_url",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_viewed",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_extra_info_link",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_extra_info_content",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"products_extra_info_content_source",
			        "Type"=>"BLOB",
			        "Null"=>"YES",
			        "Key"=>"BLOB",
			        "Default"=>null,
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"primary_category_id",
			        "Type"=>"BLOB",
			        "Null"=>"NO",
			        "Key"=>"BLOB",
			        "Default"=>"BLOB",
			        "Extra"=>""
		        ],
		        [
			        "Field"=>"adWordsCampaignID",
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

    private static function createAliases(string $tableName, ?string $relationName=null, ?array $fields=null, ?string $tableAlias=null): array
    {// 2023-06-15
        $fields = $fields ?? array_column(self::debugGetTableDefition($tableName), 'Field');

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

    private static function getTableName(string $column): ?string
    {
        $parts = explode('.', $column);

        if(count($parts) === 1) return null;

        return $parts[0];
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
    private function parseRelationQuery(string $case): ?array
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

        $relationsGraph = self::parseRelationQuery($relationName);

        $iRelations = self::createRelationsFromGraph($relationsGraph, $this->modelClass, $options);

        $this->iRelations = array_merge($iRelations, $this->iRelations);

        return $this;
    }

    private function _modifyGraph(array $targetsGraph, callable $callback, array $iRelations)
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
        else if($relationType === Model::AS_ONE_RELATION)
        {
            return $this->withGraphJoinedAsOne($iRelation, $prefix);
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
     * @return QueryBuilder
     */
    private function withGraphJoinedBelongsToOne(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $relatedModelClass = $iRelation->getRelatedModelClass();
        $relatedTableName = $relatedModelClass::getTableName();

        $relationName = implode(':', array_filter([$aliasPrefix, $iRelation->getName()]));

        $this->leftJoin($relatedTableName.' AS '.$relationName, $iRelation->getToColumn($relationName), $iRelation->getFromColumn());

        $aliasses = self::createAliases($relatedTableName, $relationName);

        $this->select($aliasses);

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $relationName);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return QueryBuilder
     */
    private function withGraphJoinedHasMany(Relation $iRelation, ?string $aliasPrefix=null): self
    {
        $relatedModelClass = $iRelation->getRelatedModelClass();
        $relatedTableName = $relatedModelClass::getTableName();

        $relationName = implode(':', array_filter([$aliasPrefix, $iRelation->getName()]));

        // FIXME: from column should be previous alised table
        $this->leftJoin($relatedTableName.' AS '.$relationName, $iRelation->getToColumn($relationName), $iRelation->getFromColumn());

        $aliasses = self::createAliases($relatedTableName, $relationName);

        $this->select($aliasses);

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $relationName);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return QueryBuilder
     */
    private function withGraphJoinedAsOne(Relation $iRelation): self
    {
        // TODO: Implement me!
    }

    /**
     * 2023-06-15
     * @return QueryBuilder
     */
    private function withGraphJoinedManyToMany(Relation $iRelation, $aliasPrefix): self
    {
        $relatedModelClass = $iRelation->getRelatedModelClass();
        $relatedTableName = $relatedModelClass::getTableName();
        $relationName = implode(':', array_filter([$aliasPrefix, $iRelation->getName()]));

        $throughTableName = self::getTableName($iRelation->getThroughFromColumn());
        $throughRelationName = implode(':', array_filter([$aliasPrefix, $iRelation->getName()])).'_through';

        $this->leftJoin($throughTableName.' AS '.$throughRelationName, $iRelation->getThroughFromColumn($throughRelationName), $iRelation->getFromColumn());
        $this->leftJoin($relatedTableName.' AS '.$relationName, $iRelation->getToColumn($relationName), $iRelation->getThroughToColumn($throughRelationName));

        $aliasses = self::createAliases($relatedTableName, $relationName);
        $extraAliases = self::createAliases($throughRelationName, $relationName, $iRelation->getThroughExtras(), $throughRelationName);

        $this->select(array_merge($aliasses, $extraAliases));

        foreach($iRelation->getChildRelations() as $iChildRelation)
        {
            $this->withRelationJoined($iChildRelation, $relationName);
        }

        return $this;
    }

    /**
     * 2023-06-15
     * @return ModelQueryBuilder
     */
    private function withGraphJoinedOneThroughRelation(Relation $iRelation): self
    {

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
    private function createModelsFromResults(array $results)
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
    private function preCompile(): void
    {
        foreach($this->iRelations as $iRelation)
        {
            $this->withRelationJoined($iRelation);
        }
    }

    /**
     * 2023-06-12
     * @return Model[]|Model
     */
    public function run()
    {// 2023-06-12
        $this->preCompile();

        $iQueryCompiler = new QueryCompiler($this->getClient(), $this, []);

        $iQuery = $iQueryCompiler->toSQL();

        $statement = $this->getClient()->query($iQuery);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $resultsGraph = $this->createModelsFromResults($results);

        $iModels = [];
        foreach($resultsGraph as $result)
        {
            $iModels[] = $this->modelClass::create($result, $this->iRelations);
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

