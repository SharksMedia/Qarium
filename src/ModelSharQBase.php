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
    public function modify(...$args): self
    {
        $func = $args[0] ?? null;

        if ($func === null)
        {
            return $this;
        }

        if (is_array($func))
        {
            foreach ($func as $f)
            {
                if (is_string($f))
                {
                    $f = $this->resolveModifier($f);
                }

                $this->modify($f, ...array_slice($args, 1));
            }

            return $this;
        }

        if (is_string($func))
        {
            $func = $this->resolveModifier($func);
        }

        $args[0] = $this;

        $func(...$args);

        return $this;
    }

    private function resolveModifier(string $modifier): callable
    {
        $modifiers = $this->modelClass::getModifiers();

        $fn = $modifiers[$modifier] ?? null;

        if ($fn === null)
        {
            $this->modelClass::modifierNotFound($this, $modifier);
        }

        return $fn;
    }

    /**
     * @param array $arg
     */
    public function transacting($trx): self
    {
        $this->context->iClient = $trx;

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
    public function delete(...$args): self
    {
        return $this->addOperation(new SharQOperation('delete'), $args);
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
        return $this->addOperation(new SharQOperation('forUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forShare(...$args): self
    {
        return $this->addOperation(new SharQOperation('forShare'), $args);
    }

    /**
     * @param array $args
     */
    public function forNoKeyUpdate(...$args): self
    {
        return $this->addOperation(new SharQOperation('forNoKeyUpdate'), $args);
    }

    /**
     * @param array $args
     */
    public function forKeyShare(...$args): self
    {
        return $this->addOperation(new SharQOperation('forKeyShare'), $args);
    }

    /**
     * @param array $args
     */
    public function skipLocked(...$args): self
    {
        return $this->addOperation(new SharQOperation('skipLocked'), $args);
    }

    /**
     * @param array $args
     */
    public function noWait(...$args): self
    {
        return $this->addOperation(new SharQOperation('noWait'), $args);
    }

    /**
     * @param array $args
     */
    public function as(...$args): self
    {
        return $this->addOperation(new SharQOperation('as'), $args);
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
        return $this->addOperation(new SharQOperation('from'), $args);
    }

    /**
     * @param array $args
     */
    public function fromJS(...$args): self
    {
        return $this->addOperation(new SharQOperation('fromJS'), $args);
    }

    /**
     * @param array $args
     */
    public function fromRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('fromRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function into(...$args): self
    {
        return $this->addOperation(new SharQOperation('into'), $args);
    }

    /**
     * @param array $args
     */
    public function withSchema($schema): self
    {
        return $this->addOperation(new SharQOperation('withSchema'), [$schema]);
    }

    /**
     * @param array $args
     */
    public function table(...$args): self
    {
        return $this->addOperation(new SharQOperation('table'), $args);
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
        return $this->addOperation(new SharQOperation('join'), $args);
    }

    /**
     * @param array $args
     */
    public function joinRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('joinRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function innerJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('innerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('leftJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function leftOuterJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('leftOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('rightJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function rightOuterJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('rightOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function outerJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('outerJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function fullOuterJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('fullOuterJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function crossJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('crossJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function where(...$args): self
    {
        return $this->addOperation(new SharQOperation('where'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhere(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhere(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhere'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNot(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNot(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNot(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNot'), $args);
    }

    /**
     * @param array $args
     */
    public function whereRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function whereWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function havingWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function whereExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function whereIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function whereBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function whereLike(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereLike(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereLike(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereLike'), $args);
    }

    /**
     * @param array $args
     */
    public function whereILike(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereILike(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereILike(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereILike'), $args);
    }

    /**
     * @param array $args
     */
    public function groupBy(...$args): self
    {
        return $this->addOperation(new SharQOperation('groupBy'), $args);
    }

    /**
     * @param array $args
     */
    public function groupByRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('groupByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orderBy(...$args): self
    {
        return $this->addOperation(new SharQOperation('orderBy'), $args);
    }

    /**
     * @param array $args
     */
    public function orderByRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('orderByRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function union(...$args): self
    {
        return $this->addOperation(new SharQOperation('union'), $args);
    }

    /**
     * @param array $args
     */
    public function unionAll(...$args): self
    {
        return $this->addOperation(new SharQOperation('unionAll'), $args);
    }

    /**
     * @param array $args
     */
    public function intersect(...$args): self
    {
        return $this->addOperation(new SharQOperation('intersect'), $args);
    }

    /**
     * @param array $args
     */
    public function having(...$args): self
    {
        return $this->addOperation(new SharQOperation('having'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHaving(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function clearGroup(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearGroup'), $args);
    }

    /**
     * @param array $args
     */
    public function clearWith(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearWith'), $args);
    }

    /**
     * @param array $args
     */
    public function clearJoin(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearJoin'), $args);
    }

    /**
     * @param array $args
     */
    public function clearUnion(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearUnion'), $args);
    }

    /**
     * @param array $args
     */
    public function clearHintComments(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearHintComments'), $args);
    }

    /**
     * @param array $args
     */
    public function clearCounters(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearCounters'), $args);
    }

    /**
     * @param array $args
     */
    public function clearLimit(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearLimit'), $args);
    }

    /**
     * @param array $args
     */
    public function clearOffset(...$args): self
    {
        return $this->addOperation(new SharQOperation('clearOffset'), $args);
    }

    /**
     * @param array $args
     */
    public function orHaving(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHaving'), $args);
    }

    /**
     * @param array $args
     */
    public function havingIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotNull(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingNotNull'), $args);
    }

    /**
     * @param array $args
     */
    public function havingExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotExists(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingNotExists'), $args);
    }

    /**
     * @param array $args
     */
    public function havingBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingNotBetween(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingNotBetween'), $args);
    }

    /**
     * @param array $args
     */
    public function havingRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('havingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingRaw(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingRaw'), $args);
    }

    /**
     * @param array $args
     */
    public function offset(...$args): self
    {
        return $this->addOperation(new SharQOperation('offset'), $args);
    }

    /**
     * @param array $args
     */
    public function limit(...$args): self
    {
        return $this->addOperation(new SharQOperation('limit'), $args);
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
    public function debug(bool $doIt = true): self
    {
        return $this->addOperation(new SharQOperation('debug'), [$doIt]);
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
        return $this->addOperation(new SharQOperation('truncate'), $args);
    }

    /**
     * @param array $args
     */
    public function connection(...$args): self
    {
        return $this->addOperation(new SharQOperation('connection'), $args);
    }

    /**
     * @param array $args
     */
    public function options(...$args): self
    {
        return $this->addOperation(new SharQOperation('options'), $args);
    }

    /**
     * @param array $args
     */
    public function columnInfo(...$args): self
    {
        return $this->addOperation(new SharQOperation('columnInfo'), $args);
    }

    /**
     * @param array $args
     */
    public function off(...$args): self
    {
        return $this->addOperation(new SharQOperation('off'), $args);
    }

    /**
     * @param array $args
     */
    public function timeout(...$args): self
    {
        return $this->addOperation(new SharQOperation('timeout'), $args);
    }

    /**
     * @param array $args
     */
    public function with(...$args): self
    {
        return $this->addOperation(new SharQOperation('with'), $args);
    }

    /**
     * @param array $args
     */
    public function withWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('withWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursive(...$args): self
    {
        return $this->addOperation(new SharQOperation('withRecursive'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterialized(...$args): self
    {
        return $this->addOperation(new SharQOperation('withMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterialized(...$args): self
    {
        return $this->addOperation(new SharQOperation('withNotMaterialized'), $args);
    }

    /**
     * @param array $args
     */
    public function withRecursiveWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('withRecursiveWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withMaterializedWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('withMaterializedWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function withNotMaterializedWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('withNotMaterializedWrapped'), $args);
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
        return $this->addOperation(new SharQOperation('whereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereColumn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereColumn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function whereNotColumn(...$args): self
    {
        return $this->addOperation(new SharQOperation('whereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotColumn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function orWhereNotColumn(...$args): self
    {
        return $this->addOperation(new SharQOperation('orWhereNotColumn'), $args);
    }

    /**
     * @param array $args
     */
    public function onConflict(...$args): self
    {
        return $this->addOperation(new SharQOperation('onConflict'), $args);
    }

    /**
     * @param array $args
     */
    public function ignore(...$args): self
    {
        return $this->addOperation(new SharQOperation('ignore'), $args);
    }

    /**
     * @param array $args
     */
    public function merge(...$args): self
    {
        return $this->addOperation(new MergeOperation('merge'), $args);
    }

    /**
     * @param array $args
     */
    public function hintComment(...$args): self
    {
        return $this->addOperation(new SharQOperation('hintComment'), $args);
    }

    /**
     * @param array $args
     */
    public function comment(...$args): self
    {
        return $this->addOperation(new SharQOperation('comment'), $args);
    }

    /**
     * @param array $args
     */
    public function or(...$args): self
    {
        return $this->addOperation(new SharQOperation('or'), $args);
    }

    /**
     * @param array $args
     */
    public function not(...$args): self
    {
        return $this->addOperation(new SharQOperation('not'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereIn'), $args);
    }

    /**
     * @param array $args
     */
    public function andWhereNotIn(...$args): self
    {
        return $this->addOperation(new SharQOperation('andWhereNotIn'), $args);
    }

    /**
     * @param array $args
     */
    public function clone(...$args): self
    {
        return $this->addOperation(new SharQOperation('clone'), $args);
    }

    /**
     * @param array $args
     */
    public function orHavingWrapped(...$args): self
    {
        return $this->addOperation(new SharQOperation('orHavingWrapped'), $args);
    }

    /**
     * @param array $args
     */
    public function transaction(...$args): self
    {
        return $this->addOperation(new SharQOperation('transaction'), $args);
    }
}
