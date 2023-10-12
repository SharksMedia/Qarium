<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\RelationJoiner;

class JoinEagerOperation extends EagerOperation
{
    private ?RelationJoiner $iJoiner = null;

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $iBuilder->setFindOption('callAfterFindDeeply', true);

        $this->iJoiner = new RelationJoiner(['modelClass' => $iBuilder->getModelClass()]);

        return true;
    }

    public function onBefore3(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        return !!$this->iJoiner
            ->setExpression($this->buildFinalExpression())
            ->setModifiers($this->buildFinalModifiers($iBuilder))
            ->setOptions($this->getGraphOptions())
            ->fetchColumnInfo($iBuilder);
    }

    public function onBuild(ModelSharQOperationSupport $iBuilder): void
    {
        $this->iJoiner
            ->setExpression($this->buildFinalExpression())
            ->setModifiers($this->buildFinalModifiers($iBuilder))
            ->setOptions($this->getGraphOptions())
            ->build($iBuilder);
    }

    public function onRawResult(ModelSharQOperationSupport $iBuilder, array $rows)
    {
        return $this->iJoiner->parseResult($iBuilder, $rows);
    }
}
