<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\StaticHookArguments;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;

class DeleteOperation extends ModelQueryBuilderOperation
{
    public function onBefore2(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->callBeforeDelete($iBuilder);

        return true;
    }

    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        return $iQueryBuilder->delete();
    }

    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        return $this->callAfterDelete($iBuilder, $result);
    }

    public function toFindOperation(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperation
    {
        return null;
    }

    private function callBeforeDelete(ModelQueryBuilder $iBuilder)
    {
        return $this->callStaticBeforeDelete($iBuilder);
    }

    private function callStaticBeforeDelete(ModelQueryBuilder $iBuilder)
    {
        $args = StaticHookArguments::create($iBuilder);

        /** @var class-string<\Sharksmedia\Objection\Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::beforeDelete($args);
    }

    private function callAfterDelete(ModelQueryBuilder $builder, $result)
    {
        return $this->callStaticAfterDelete($builder, $result);
    }

    private function callStaticAfterDelete(ModelQueryBuilder $iBuilder, $result)
    {
        $args = StaticHookArguments::create($iBuilder, $result);

        /** @var class-string<\Sharksmedia\Objection\Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        $maybeResult = $modelClass::afterDelete($args);

        if($maybeResult !== null) return $maybeResult;

        return $result;
    }
}
