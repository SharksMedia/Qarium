<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Objection\Operations\SelectOperation;
use Sharksmedia\Objection\Operations\QueryBuilderOperation;
use Sharksmedia\Objection\Operations\ReturningOperation;
use Sharksmedia\Objection\Operations\WhereCompositeOperation;
use Sharksmedia\Objection\Operations\WhereInCompositeOperation;
use Sharksmedia\Objection\Operations\MergeOperation;

class ModelQueryBuilderBase extends ModelQueryBuilderOperationSupport
{
    /**
     * @param array $arg
     */
    public function modify(...$args): self
    {
        $func = $args[0] ?? null;

        if($func === null) return $this;

        $args[0] = $this;

        $func(...$args);

        return $this;
    }

    /**
     * @param array $arg
     */
    public function transacting($trx): self
    {
        $this->context->iQueryBuilder = $trx;

         return $this;
    }

    /**
     * @param array $args
     */
    public function select(...$args): self
    {
         return $this->addOperation(new SelectOperation('select'), $args);
    }

    /**
     * @param array $args
     */
    public function insert($modelsOrObjects): self
    {
         return $this->addOperation(new QueryBuilderOperation('insert'), $args);
    }

    /**
     * @param array $args
     */
    public function update(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('update'), $args);
    }

    /**
     * @param array $args
     */
    public function delete(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('delete'), $args);
    }
    /**
     * @param array $args
     */
    public function del(...$args): self
    {
         return $this->delete(...$args);
    }

    /**
     * @param array $args
     */
    public function forUpdate(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('forUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forShare(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('forShare'), $args);
    }

    /**
     * @param array $args
     */
    public function forNoKeyUpdate(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('forNoKeyUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forKeyShare(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('forKeyShare'), $args);
    }

    /**
     * @param array $args
     */
    public function skipLocked(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('skipLocked'), $args);
    }

    /**
     * @param array $args
     */
    public function noWait(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('noWait'), $args);
    }

    /**
     * @param array $args
     */
    public function as(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('as'), $args);
    }

    /**
     * @param array $args
     */
    public function columns(...$args): self
    {
         return $this->addOperation(new SelectOperation('columns'), $args);
    }

    /**
     * @param array $args
     */
    public function column(...$args): self
    {
         return $this->addOperation(new SelectOperation('column'), $args);
    }

    /**
     * @param array $args
     */
    public function from(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('from'), $args);
    }

    /**
     * @param array $args
     */
    public function fromJS(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('fromJS'), $args);
    }

    /**
     * @param array $args
     */
    public function fromRaw(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('fromRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function into(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('into'), $args);
    }

    /**
     * @param array $args
     */
    public function withSchema(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('withSchema'), $args);
    }

    /**
     * @param array $args
     */
    public function table(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('table'), $args);
    }

    /**
     * @param array $args
     */
    public function distinct(...$args): self
    {
         return $this->addOperation(new SelectOperation('distinct'), $args);
    }

    /**
     * @param array $args
     */
    public function distinctOn(...$args): self
    {
         return $this->addOperation(new SelectOperation('distinctOn'), $args);
    }

    /**
     * @param array $args
     */
    public function join(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('join'), $args);
    }

    /**
     * @param array $args
     */
    public function joinRaw(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('joinRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function innerJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('innerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('leftJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftOuterJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('leftOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('rightJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightOuterJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('rightOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function outerJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('outerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function fullOuterJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('fullOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function crossJoin(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('crossJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function where(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('where'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhere(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('andWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhere(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('orWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNot(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('whereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNot(...$args): self
    {
         return $this->addOperation(new QueryBuilderOperation('andWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNot(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function whereRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function whereWrapped(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function havingWrapped(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function whereExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereLike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereLike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereLike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function whereILike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereILike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereILike(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function groupBy(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('groupBy'), $args);
    }

    /**
     * @param array $args
     */
    public function groupByRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('groupByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orderBy(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orderBy'), $args);
    }

    /**
     * @param array $args
     */
    public function orderByRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orderByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function union(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('union'), $args);
    }

    /**
     * @param array $args
     */
    public function unionAll(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('unionAll'), $args);
    }

    /**
     * @param array $args
     */
    public function intersect(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('intersect'), $args);
    }

    /**
     * @param array $args
     */
    public function having(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('having'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHaving(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('clearHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function clearGroup(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('clearGroup'), $args);
    }

    /**
     * @param array $args
     */
    public function orHaving(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function havingIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotIn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotNull(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotExists(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotBetween(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('havingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingRaw(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function offset(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('offset'), $args);
    }

    /**
     * @param array $args
     */
    public function limit(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('limit'), $args);
    }

    /**
     * @param array $args
     */
    public function count(...$args): self
    {
        return $this->addOperation(new SelectOperation('count'), $args);
    }

    /**
     * @param array $args
     */
    public function countDistinct(...$args): self
    {
        return $this->addOperation(new SelectOperation('countDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function min(...$args): self
    {
        return $this->addOperation(new SelectOperation('min'), $args);
    }

    /**
     * @param array $args
     */
    public function max(...$args): self
    {
        return $this->addOperation(new SelectOperation('max'), $args);
    }

    /**
     * @param array $args
     */
    public function sum(...$args): self
    {
        return $this->addOperation(new SelectOperation('sum'), $args);
    }

    /**
     * @param array $args
     */
    public function sumDistinct(...$args): self
    {
        return $this->addOperation(new SelectOperation('sumDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function avg(...$args): self
    {
        return $this->addOperation(new SelectOperation('avg'), $args);
    }

    /**
     * @param array $args
     */
    public function avgDistinct(...$args): self
    {
        return $this->addOperation(new SelectOperation('avgDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function debug(bool $doIt=true): self
    {
        return $this->addOperation(new QueryBuilderOperation('debug'), [$doIt]);
    }

    /**
     * @param array $args
     */
    public function     returning(...$args): self
    {
        return $this->addOperation(new ReturningOperation('returning'), $args);
    }

    /**
     * @param array $args
     */
    public function truncate(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('truncate'), $args);
    }

    /**
     * @param array $args
     */
    public function connection(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('connection'), $args);
    }

    /**
     * @param array $args
     */
    public function options(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('options'), $args);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('columnInfo'), $args);
    }

    /**
     * @param array $args
     */
    public function off(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('off'), $args);
    }

    /**
     * @param array $args
     */
    public function timeout(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('timeout'), $args);
    }

    /**
     * @param array $args
     */
    public function with(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('with'), $args);
    }

    /**
     * @param array $args
     */
    public function withWrapped(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('withWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursive(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('withRecursive'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterialized(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('withMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterialized(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('withNotMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function whereComposite(...$args): self
    {
        return $this->addOperation(new WhereCompositeOperation('whereComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereInComposite(...$args): self
    {
        return $this->addOperation(new WhereInCompositeOperation('whereInComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotInComposite(...$args): self
    {
        return $this->addOperation(new WhereInCompositeOperation('whereNotInComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotColumn(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function onConflict(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('onConflict'), $args);
    }

    /**
     * @param array $args
     */
    public function ignore(...$args): self
    {
        return $this->addOperation(new QueryBuilderOperation('ignore'), $args);
    }

    /**
     * @param array $args
     */
    public function merge(...$args): self
    {
        return $this->addOperation(new MergeOperation('merge'), $args);
    }
}
