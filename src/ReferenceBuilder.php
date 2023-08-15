<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-12

class ReferenceBuilder
{

    /**
     * 2023-07-12
     * @var string
     */
    private ?string $expr;

    /**
     * 2023-07-12
     * @var object
     */
    private ?object $parsedExpr;

    /**
     * 2023-07-12
     * @var string
     */
    private ?string $column;

    /**
     * 2023-07-12
     * @var string
     */
    private ?string $table;

    /**
     * 2023-07-12
     * @var string
     */
    private ?string $cast = null;

    /**
     * 2023-07-12
     * @var bool
     */
    private bool $toJson = false;

    /**
     * 2023-07-12
     * @var string
     */
    private ?string $alias = null;

    /**
     * 2023-07-12
     * @var class-string<Model>
     */
    private ?string $modelClass;

    public function __construct(?string $expr=null)
    {
        $this->expr = $expr;

        if($expr !== null) $this->parseExpression($expr);
    }

    public function getExpression(): ?string
    {
        return $this->expr;
    }

    public function getParsedExpression(): object
    {
        return $this->parsedExpr;
    }

    public function getParsedExpr(): ?object
    {
        return $this->parsedExpr;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function getTableName(): ?string
    {
        return $this->table;
    }

    public function getCast(): ?string
    {
        return $this->cast;
    }

    public function getToJson(): bool
    {
        return $this->toJson;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    public function isPlainColumnRef(): bool
    {
        return ($this->parsedExpr === null || count($this->parsedExpr->access) === 0) && $this->cast === null && !$this->toJson;
    }

    public function fullColumn(ModelQueryBuilderOperationSupport $iBuilder)
    {
        $table = null;
        if($this->getTableName() !== null) $table = $this->getTableName();
        else if($this->modelClass !== null) $table = $iBuilder->getTableRefFor($this->modelClass);

        if($table !== null) return $table.'.'.$this->column;

        return $this->column;
    }


    public function castText(): self
    {
        return $this->castTo('text');
    }

    public function castInt(): self
    {
        return $this->castTo('integer');
    }

    public function castBigInt(): self
    {
        return $this->castTo('bigint');
    }

    public function castFloat(): self
    {
        return $this->castTo('float');
    }

    public function castDecimal(): self
    {
        return $this->castTo('decimal');
    }

    public function castReal(): self
    {
        return $this->castTo('real');
    }

    public function castBool(): self
    {
        return $this->castTo('boolean');
    }

    public function castJson(): self
    {
        $this->toJson = true;
        return $this;
    }

    public function castTo($sqlType): self
    {
        $this->cast = $sqlType;
        return $this;
    }

    public function from($table)
    {
        return $this->table($table);
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function model(string $modelClass)
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function as($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function toQueryBuilderRaw(ModelQueryBuilderOperationSupport $iBuilder): \Sharksmedia\QueryBuilder\Statement\Raw
    {
        return new \Sharksmedia\QueryBuilder\Statement\Raw(...$this->createRawArgs($iBuilder));
    }

    public function parseExpression(string $expr)
    {
        $this->parsedExpr = Utilities::parseFieldExpression($expr);
        $this->column = $this->parsedExpr->column;
        $this->table = $this->parsedExpr->table;
    }

    public function createRawArgs(ModelQueryBuilderOperationSupport $iBuilder)
    {
        $bindings = [];
        $sql = $this->createReferenceSql($iBuilder, $bindings);

        $sql = $this->maybeCast($sql, $bindings);
        $sql = $this->maybeToJsonb($sql, $bindings);
        $sql = $this->maybeAlias($sql, $bindings);

        return [$sql, ...$bindings];
    }

    private function createReferenceSql(ModelQueryBuilderOperationSupport $iBuilder, array &$bindings)
    {
        $bindings[] = $this->fullColumn($iBuilder);

        if(count($this->parsedExpr->access) > 0)
        {
            $extractor = $this->cast ? '#>>' : '#>';

            $jsonFieldRef = implode(',', array_map(function($field) { return $field->ref; }, $this->parsedExpr->access));

            return '??'.$extractor."'{{$jsonFieldRef}}'";
        }

        return '??';
    }

    private function maybeCast($sql, array &$bindings)
    {
        if($this->cast !== null) $sql = 'CAST('.$sql.' AS '.$this->cast.')';

        return $sql;
    }

    private function maybeToJsonb($sql, array &$bindings)
    {
        if($this->toJson) return 'to_jsonb('.$sql.')';

        return $sql;
    }

    private function maybeAlias($sql, array &$bindings)
    {
        if($this->alias !== null)
        {
            $bindings[] = $this->alias;
            $sql = $sql.' AS ??';
        }

        return $sql;
    }

    private function shouldAlias()
    {
        if($this->alias === null) return false;

        if($this->isPlainColumnRef()) return true;

        // No need to alias if we are dealing with a simple column reference
        // and the alias is the same as the column name.
        return $this->alias !== $this->column;
    }

    public static function ref($reference): ReferenceBuilder
    {
        if(is_object($reference) && $reference instanceof ReferenceBuilder) return $reference;

        return new ReferenceBuilder($reference);
    }






}
