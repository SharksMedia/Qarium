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

/**
 * Place \Sharksmedia\QueryBuilder functions here
 */
class ModelQueryBuilderBase extends ModelQueryBuilderOperationSupport
{
    /**
     * @param array $arg
     */
    public function modify(...$args): static
    {
        $func = $args[0] ?? null;

        if($func === null) return $this;

        if(is_array($func))
        {
            foreach($func as $f)
            {
                if(is_string($f)) $f = $this->resolveModifier($f);

                $this->modify($f, ...array_slice($args, 1));
            }

            return $this;
        }

        if(is_string($func)) $func = $this->resolveModifier($func);

        $args[0] = $this;

        $func(...$args);

        return $this;
    }

    private function resolveModifier(string $modifier): callable
    {
        $modifiers = $this->modelClass::getModifiers();

        $fn = $modifiers[$modifier] ?? null;

        if($fn === null) $this->modelClass::modifierNotFound($this, $modifier);

        return $fn;
    }

    /**
     * @param array $arg
     */
    public function transacting($trx): static
    {
        $this->context->iQueryBuilder = $trx;

         return $this;
    }

    /**
     * @param array $args
     */
    public function select(...$args): static
    {
         return $this->addOperation(new SelectOperation('select'), $args);
    }

    /**
     * @param array $args
     */
    public function insert($modelsOrObjects): static
    {
         return $this->addOperation(new QueryBuilderOperation('insert'), $args);
    }

    /**
     * @param array $args
     */
    public function update($modelOrObject): self
    {
         return $this->addOperation(new QueryBuilderOperation('update'), [$modelOrObject]);
    }

    /**
     * @param array $args
     */
    public function delete(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('delete'), $args);
    }
    /**
     * @param array $args
     */
    public function del(...$args): static
    {
         return $this->delete(...$args);
    }

    /**
     * @param array $args
     */
    public function forUpdate(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('forUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forShare(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('forShare'), $args);
    }

    /**
     * @param array $args
     */
    public function forNoKeyUpdate(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('forNoKeyUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forKeyShare(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('forKeyShare'), $args);
    }

    /**
     * @param array $args
     */
    public function skipLocked(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('skipLocked'), $args);
    }

    /**
     * @param array $args
     */
    public function noWait(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('noWait'), $args);
    }

    /**
     * @param array $args
     */
    public function as(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('as'), $args);
    }

    /**
     * @param array $args
     */
    public function columns(...$args): static
    {
         return $this->addOperation(new SelectOperation('columns'), $args);
    }

    /**
     * @param array $args
     */
    public function column(...$args): static
    {
         return $this->addOperation(new SelectOperation('column'), $args);
    }

    /**
     * @param array $args
     */
    public function from(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('from'), $args);
    }

    /**
     * @param array $args
     */
    public function fromJS(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('fromJS'), $args);
    }

    /**
     * @param array $args
     */
    public function fromRaw(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('fromRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function into(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('into'), $args);
    }

    /**
     * @param array $args
     */
    public function withSchema($schema): static
    {
         return $this->addOperation(new QueryBuilderOperation('withSchema'), [$schema]);
    }

    /**
     * @param array $args
     */
    public function table(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('table'), $args);
    }

    /**
     * @param array $args
     */
    public function distinct(...$args): static
    {
         return $this->addOperation(new SelectOperation('distinct'), $args);
    }

    /**
     * @param array $args
     */
    public function distinctOn(...$args): static
    {
         return $this->addOperation(new SelectOperation('distinctOn'), $args);
    }

    /**
     * @param array $args
     */
    public function join(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('join'), $args);
    }

    /**
     * @param array $args
     */
    public function joinRaw(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('joinRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function innerJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('innerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('leftJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftOuterJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('leftOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('rightJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightOuterJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('rightOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function outerJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('outerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function fullOuterJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('fullOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function crossJoin(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('crossJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function where(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('where'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhere(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('andWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhere(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('orWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNot(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('whereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNot(...$args): static
    {
         return $this->addOperation(new QueryBuilderOperation('andWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNot(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function whereRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function whereWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function havingWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function whereExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereLike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereLike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereLike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function whereILike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereILike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereILike(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function groupBy(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('groupBy'), $args);
    }

    /**
     * @param array $args
     */
    public function groupByRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('groupByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orderBy(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orderBy'), $args);
    }

    /**
     * @param array $args
     */
    public function orderByRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orderByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function union(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('union'), $args);
    }

    /**
     * @param array $args
     */
    public function unionAll(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('unionAll'), $args);
    }

    /**
     * @param array $args
     */
    public function intersect(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('intersect'), $args);
    }

    /**
     * @param array $args
     */
    public function having(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('having'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHaving(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function clearGroup(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearGroup'), $args);
    }

    /**
     * @param array $args
     */
    public function clearWith(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearWith'), $args);
    }

    /**
     * @param array $args
     */
    public function clearJoin(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function clearUnion(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearUnion'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHintComments(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearHintComments'), $args);
    }

    /**
     * @param array $args
     */
    public function clearCounters(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearCounters'), $args);
    }

    /**
     * @param array $args
     */
    public function clearLimit(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearLimit'), $args);
    }

    /**
     * @param array $args
     */
    public function clearOffset(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clearOffset'), $args);
    }

    /**
     * @param array $args
     */
    public function orHaving(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function havingIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotNull(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotExists(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotBetween(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('havingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingRaw(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function offset(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('offset'), $args);
    }

    /**
     * @param array $args
     */
    public function limit(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('limit'), $args);
    }

    /**
     * @param array $args
     */
    public function count(...$args): static
    {
        return $this->addOperation(new SelectOperation('count'), $args);
    }

    /**
     * @param array $args
     */
    public function countDistinct(...$args): static
    {
        return $this->addOperation(new SelectOperation('countDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function min(...$args): static
    {
        return $this->addOperation(new SelectOperation('min'), $args);
    }

    /**
     * @param array $args
     */
    public function max(...$args): static
    {
        return $this->addOperation(new SelectOperation('max'), $args);
    }

    /**
     * @param array $args
     */
    public function sum(...$args): static
    {
        return $this->addOperation(new SelectOperation('sum'), $args);
    }

    /**
     * @param array $args
     */
    public function sumDistinct(...$args): static
    {
        return $this->addOperation(new SelectOperation('sumDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function avg(...$args): static
    {
        return $this->addOperation(new SelectOperation('avg'), $args);
    }

    /**
     * @param array $args
     */
    public function avgDistinct(...$args): static
    {
        return $this->addOperation(new SelectOperation('avgDistinct'), $args);
    }

    /**
     * @param array $args
     */
    public function debug(bool $doIt=true): static
    {
        return $this->addOperation(new QueryBuilderOperation('debug'), [$doIt]);
    }

    /**
     * @param array $args
     */
    public function     returning(...$args): static
    {
        return $this->addOperation(new ReturningOperation('returning'), $args);
    }

    /**
     * @param array $args
     */
    public function truncate(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('truncate'), $args);
    }

    /**
     * @param array $args
     */
    public function connection(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('connection'), $args);
    }

    /**
     * @param array $args
     */
    public function options(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('options'), $args);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('columnInfo'), $args);
    }

    /**
     * @param array $args
     */
    public function off(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('off'), $args);
    }

    /**
     * @param array $args
     */
    public function timeout(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('timeout'), $args);
    }

    /**
     * @param array $args
     */
    public function with(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('with'), $args);
    }

    /**
     * @param array $args
     */
    public function withWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursive(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withRecursive'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterialized(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterialized(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withNotMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursiveWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withRecursiveWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterializedWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withMaterializedWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterializedWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('withNotMaterializedWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function whereComposite(...$args): static
    {
        return $this->addOperation(new WhereCompositeOperation('whereComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereInComposite(...$args): static
    {
        return $this->addOperation(new WhereInCompositeOperation('whereInComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotInComposite(...$args): static
    {
        return $this->addOperation(new WhereInCompositeOperation('whereNotInComposite'), $args);
    }

    /**
     * @param array $args
     */
    public function whereColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('whereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotColumn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function onConflict(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('onConflict'), $args);
    }

    /**
     * @param array $args
     */
    public function ignore(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('ignore'), $args);
    }

    /**
     * @param array $args
     */
    public function merge(...$args): static
    {
        return $this->addOperation(new MergeOperation('merge'), $args);
    }

    /**
     * @param array $args
     */
    public function hintComment(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('hintComment'), $args);
    }

    /**
     * @param array $args
     */
    public function comment(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('comment'), $args);
    }

    /**
     * @param array $args
     */
    public function or(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('or'), $args);
    }

    /**
     * @param array $args
     */
    public function not(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('not'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotIn(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('andWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function clone(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('clone'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingWrapped(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('orHavingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function transaction(...$args): static
    {
        return $this->addOperation(new QueryBuilderOperation('transaction'), $args);
    }
}
