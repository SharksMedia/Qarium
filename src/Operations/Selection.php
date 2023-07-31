<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ReferenceBuilder;
use Sharksmedia\QueryBuilder\Statement\Raw;

class Selection
{
    public const ALIAS_REGEX = '/\s+as\s+/i';

    /**
     * @var string|null
     */
    private $table;

    /**
     * @var string|null
     */
    private $column;

    /**
     * @var string|null
     */
    private $alias;

    public function __construct(?string $table, ?string $column, ?string $alias=null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->alias = $alias;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getName()
    {
        return $this->alias ?? $this->column;
    }

    public static function create($selection): ?self
    {
        if(is_object($selection))
        {
            if($selection instanceof Selection) return $selection;
            else if($selection instanceof ReferenceBuilder) return self::createSelectionFromReference($selection);
            else if($selection instanceof Raw) return self::createSelectionFromRaw($selection);

            return null;
        }

        if(is_string($selection)) return self::createSelectionFromString($selection);

        return null;
    }

    public static function doesSelect($builder, $selectionInBuilder, $selectionToTest)
    {
        $iSelectionInBuilder = Selection::create($selectionInBuilder);
        $iSelectionToTest = Selection::create($selectionToTest);

        if($iSelectionInBuilder->getColumn() === '*')
        {
            if($iSelectionInBuilder->getTable() === null) return true;

            if($iSelectionToTest->getColumn() === '*') return $iSelectionInBuilder->getTable() === $iSelectionToTest->getTable();

            return $selectionToTest->getTable() === null || $iSelectionInBuilder->getTable() === $iSelectionToTest->getTable();
        }

        if($selectionToTest->getColumn() === '*') return false;

        $selectionInBuilderTable = $iSelectionInBuilder->getTable() ?? $builder->getTableRef();

        return
            (
                $iSelectionToTest->getColumn() === $iSelectionInBuilder->getColumn()
                &&
                (
                    $iSelectionToTest->getTable() === null
                    ||
                    $iSelectionToTest->getTable() === ($iSelectionInBuilder->getTable() ?? $builder->getTableRef())
                )
            );
    }

    private static function createSelectionFromReference($reference): self
    {
        return new Selection($reference->table, $reference->column, $reference->alias);
    }

    private static function createSelectionFromRaw($raw): ?self
    {
        if($raw->alias) return new Selection(null, null, $raw->alias);

        return null;
    }

    private static function createSelectionFromString(string $selection): self
    {
        $table = null;
        $column = null;
        $alias = null;

        if(preg_match(self::ALIAS_REGEX, $selection) === 1)
        {
            $parts = explode(' as ', $selection);
            $selection = trim($parts[0]);
            $alias = trim($parts[1]);
        }

        $column = $selection;

        $dotIdx = strrpos($selection, '.');

        if($dotIdx !== false)
        {
            $table = substr($selection, 0, $dotIdx);
            $column = substr($selection, $dotIdx + 1);
        }

        return new Selection($table, $column, $alias);
    }

}
