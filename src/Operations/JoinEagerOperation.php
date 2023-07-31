<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\RelationJoiner;

class JoinEagerOperation extends EagerOperation
{
    private ?RelationJoiner $iJoiner = null;

    public function onAdd(ModelQueryBuilder $iBuilder, ...$arguments): bool
    {
        $iBuilder->setFindOption('callAfterFindDeeply', true);

        $this->iJoiner = new RelationJoiner(['modelClass'=>$iBuilder->getModelClass()]);

        return true;
    }

    public function onBefore3(ModelQueryBuilder $iBuilder, ...$arguments): bool
    {
        return !!$this->iJoiner
            ->setExpression($this->buildFinalExpression())
            ->setModifiers($this->buildFinalModifiers($iBuilder))
            ->setOptions($this->getGraphOptions())
            ->fetchColumnInfo($iBuilder);
    }

    public function onBuild(ModelQueryBuilder $iBuilder): void
    {
        $this->iJoiner
            ->setExpression($this->buildFinalExpression())
            ->setModifiers($this->buildFinalModifiers($iBuilder))
            ->setOptions($this->getGraphOptions())
            ->build($iBuilder);
    }

    public function onRawResult(ModelQueryBuilder $iBuilder, array $rows)
    {
        return $this->iJoiner->parseResult($iBuilder, $rows);
    }
}
