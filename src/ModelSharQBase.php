<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\Operations\SelectOperation;
use Sharksmedia\Qarium\Operations\SharQOperation;
use Sharksmedia\Qarium\Operations\ReturningOperation;
use Sharksmedia\Qarium\Operations\WhereCompositeOperation;
use Sharksmedia\Qarium\Operations\WhereInCompositeOperation;
use Sharksmedia\Qarium\Operations\MergeOperation;

/**
 * Place \Sharksmedia\SharQ functions here
 */
class ModelSharQBase extends ModelSharQOperationSupport
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
        $this->context->iClient = $trx;

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
         return $this->addOperation(new SharQOperation('insert'), $args);
    }

    /**
     * @param array $args
     */
    public function update($modelOrObject): self
    {
         return $this->addOperation(new SharQOperation('update'), [$modelOrObject]);
    }

    /**
     * @param array $args
     */
    public function delete(...$args): static
    {
         return $this->addOperation(new SharQOperation('delete'), $args);
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
         return $this->addOperation(new SharQOperation('forUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forShare(...$args): static
    {
         return $this->addOperation(new SharQOperation('forShare'), $args);
    }

    /**
     * @param array $args
     */
    public function forNoKeyUpdate(...$args): static
    {
         return $this->addOperation(new SharQOperation('forNoKeyUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forKeyShare(...$args): static
    {
         return $this->addOperation(new SharQOperation('forKeyShare'), $args);
    }

    /**
     * @param array $args
     */
    public function skipLocked(...$args): static
    {
         return $this->addOperation(new SharQOperation('skipLocked'), $args);
    }

    /**
     * @param array $args
     */
    public function noWait(...$args): static
    {
         return $this->addOperation(new SharQOperation('noWait'), $args);
    }

    /**
     * @param array $args
     */
    public function as(...$args): static
    {
         return $this->addOperation(new SharQOperation('as'), $args);
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
         return $this->addOperation(new SharQOperation('from'), $args);
    }

    /**
     * @param array $args
     */
    public function fromJS(...$args): static
    {
         return $this->addOperation(new SharQOperation('fromJS'), $args);
    }

    /**
     * @param array $args
     */
    public function fromRaw(...$args): static
    {
         return $this->addOperation(new SharQOperation('fromRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function into(...$args): static
    {
         return $this->addOperation(new SharQOperation('into'), $args);
    }

    /**
     * @param array $args
     */
    public function withSchema($schema): static
    {
         return $this->addOperation(new SharQOperation('withSchema'), [$schema]);
    }

    /**
     * @param array $args
     */
    public function table(...$args): static
    {
         return $this->addOperation(new SharQOperation('table'), $args);
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
         return $this->addOperation(new SharQOperation('join'), $args);
    }

    /**
     * @param array $args
     */
    public function joinRaw(...$args): static
    {
         return $this->addOperation(new SharQOperation('joinRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function innerJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('innerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('leftJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftOuterJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('leftOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('rightJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightOuterJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('rightOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function outerJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('outerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function fullOuterJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('fullOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function crossJoin(...$args): static
    {
         return $this->addOperation(new SharQOperation('crossJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function where(...$args): static
    {
         return $this->addOperation(new SharQOperation('where'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhere(...$args): static
    {
         return $this->addOperation(new SharQOperation('andWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhere(...$args): static
    {
         return $this->addOperation(new SharQOperation('orWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNot(...$args): static
    {
         return $this->addOperation(new SharQOperation('whereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNot(...$args): static
    {
         return $this->addOperation(new SharQOperation('andWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNot(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function whereRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function whereWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function havingWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function whereExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereLike(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereLike(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereLike(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function whereILike(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereILike(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereILike(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function groupBy(...$args): static
    {
        return $this->addOperation(new SharQOperation('groupBy'), $args);
    }

    /**
     * @param array $args
     */
    public function groupByRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('groupByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orderBy(...$args): static
    {
        return $this->addOperation(new SharQOperation('orderBy'), $args);
    }

    /**
     * @param array $args
     */
    public function orderByRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('orderByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function union(...$args): static
    {
        return $this->addOperation(new SharQOperation('union'), $args);
    }

    /**
     * @param array $args
     */
    public function unionAll(...$args): static
    {
        return $this->addOperation(new SharQOperation('unionAll'), $args);
    }

    /**
     * @param array $args
     */
    public function intersect(...$args): static
    {
        return $this->addOperation(new SharQOperation('intersect'), $args);
    }

    /**
     * @param array $args
     */
    public function having(...$args): static
    {
        return $this->addOperation(new SharQOperation('having'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHaving(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function clearGroup(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearGroup'), $args);
    }

    /**
     * @param array $args
     */
    public function clearWith(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearWith'), $args);
    }

    /**
     * @param array $args
     */
    public function clearJoin(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function clearUnion(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearUnion'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHintComments(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearHintComments'), $args);
    }

    /**
     * @param array $args
     */
    public function clearCounters(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearCounters'), $args);
    }

    /**
     * @param array $args
     */
    public function clearLimit(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearLimit'), $args);
    }

    /**
     * @param array $args
     */
    public function clearOffset(...$args): static
    {
        return $this->addOperation(new SharQOperation('clearOffset'), $args);
    }

    /**
     * @param array $args
     */
    public function orHaving(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function havingIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotNull(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotExists(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotBetween(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('havingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingRaw(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function offset(...$args): static
    {
        return $this->addOperation(new SharQOperation('offset'), $args);
    }

    /**
     * @param array $args
     */
    public function limit(...$args): static
    {
        return $this->addOperation(new SharQOperation('limit'), $args);
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
        return $this->addOperation(new SharQOperation('debug'), [$doIt]);
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
        return $this->addOperation(new SharQOperation('truncate'), $args);
    }

    /**
     * @param array $args
     */
    public function connection(...$args): static
    {
        return $this->addOperation(new SharQOperation('connection'), $args);
    }

    /**
     * @param array $args
     */
    public function options(...$args): static
    {
        return $this->addOperation(new SharQOperation('options'), $args);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): static
    {
        return $this->addOperation(new SharQOperation('columnInfo'), $args);
    }

    /**
     * @param array $args
     */
    public function off(...$args): static
    {
        return $this->addOperation(new SharQOperation('off'), $args);
    }

    /**
     * @param array $args
     */
    public function timeout(...$args): static
    {
        return $this->addOperation(new SharQOperation('timeout'), $args);
    }

    /**
     * @param array $args
     */
    public function with(...$args): static
    {
        return $this->addOperation(new SharQOperation('with'), $args);
    }

    /**
     * @param array $args
     */
    public function withWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('withWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursive(...$args): static
    {
        return $this->addOperation(new SharQOperation('withRecursive'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterialized(...$args): static
    {
        return $this->addOperation(new SharQOperation('withMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterialized(...$args): static
    {
        return $this->addOperation(new SharQOperation('withNotMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursiveWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('withRecursiveWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterializedWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('withMaterializedWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterializedWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('withNotMaterializedWrapped'), $args);
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
        return $this->addOperation(new SharQOperation('whereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereColumn(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereColumn(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotColumn(...$args): static
    {
        return $this->addOperation(new SharQOperation('whereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotColumn(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotColumn(...$args): static
    {
        return $this->addOperation(new SharQOperation('orWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function onConflict(...$args): static
    {
        return $this->addOperation(new SharQOperation('onConflict'), $args);
    }

    /**
     * @param array $args
     */
    public function ignore(...$args): static
    {
        return $this->addOperation(new SharQOperation('ignore'), $args);
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
        return $this->addOperation(new SharQOperation('hintComment'), $args);
    }

    /**
     * @param array $args
     */
    public function comment(...$args): static
    {
        return $this->addOperation(new SharQOperation('comment'), $args);
    }

    /**
     * @param array $args
     */
    public function or(...$args): static
    {
        return $this->addOperation(new SharQOperation('or'), $args);
    }

    /**
     * @param array $args
     */
    public function not(...$args): static
    {
        return $this->addOperation(new SharQOperation('not'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotIn(...$args): static
    {
        return $this->addOperation(new SharQOperation('andWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function clone(...$args): static
    {
        return $this->addOperation(new SharQOperation('clone'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingWrapped(...$args): static
    {
        return $this->addOperation(new SharQOperation('orHavingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function transaction(...$args): static
    {
        return $this->addOperation(new SharQOperation('transaction'), $args);
    }
}
