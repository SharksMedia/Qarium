<?php

declare(strict_types=1);

namespace Sharksmedia\Objection\Transformations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\Operations\QueryBuilderOperation;

class WrapMysqlModifySubqueryTransformation extends QueryTransformation
{

    /**
     * Mysql doesn't allow queries like this:
     *
     *   update foo set bar = 1 where id in (select id from foo)
     *
     * because the subquery is for the same table `foo` as the parent update query.
     * The same goes for delete queries too.
     *
     * This transformation wraps those subqueries like this:
     *
     * update foo set bar = 1 where id in (select * from (select id from foo))
     *
     * @param ModelQueryBuilder $iQuery
     * @param ModelQueryBuilder $iBuilder parent query builder
     * @return ModelQueryBuilder
     */
    public function onConvertQueryBuilderBase(ModelQueryBuilder $iQuery, ModelQueryBuilder $iBuilder): ModelQueryBuilder
    {
        $iQueryBuilder = $iBuilder->getUnsafeQueryBuilder();

        // Cannot detect anything if, for whatever reason, a knex instance
        // or a transaction is not registered at this point.
        if (!$iQueryBuilder) return $iQuery;

        // This transformation only applies to MySQL.
        if(!($iQueryBuilder->getClient() instanceof \Sharksmedia\QueryBuilder\Client\MySQL)) return $iQuery;

        // This transformation only applies to update and delete queries.
        if(!$iBuilder->isUpdate() && !$iBuilder->isDelete()) return $iQuery;

        $hasSameTableName = $iBuilder->getTableName() !== $iQuery->getTableName();

        // If the subquery is for another table and the query doesn't join the
        // parent query's table, we're good to go.
        if($hasSameTableName && !self::hasJoinsToTable($iQuery, $iBuilder->getTableName())) return $iQuery;

        /** @var class-string<\Sharksmedia\Objection\Model> $getModelClass */
        $modelClass = $iQuery->getModelClass();

        return $modelClass::query()->from($iQuery->as('mysql_subquery_fix'));
    }

    private static function hasJoinsToTable(ModelQueryBuilder $iQuery, string $tableName): bool
    {
        $found = false;

        $iQuery->forEachOperations(ModelQueryBuilder::JOIN_SELECTOR, function(QueryBuilderOperation $op) use ($tableName, &$found)
        {
            $arguemnts = $op->getArgumentsRaw();
            if($arguemnts[0] === $tableName)
            {
                $found = true;
                return false;
            }

            return null;
        });

        return $found;
    }

}
