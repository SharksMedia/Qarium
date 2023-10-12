<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\StaticHookArguments;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class DeleteOperation extends ModelSharQOperation
{
    public function onBefore2(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->callBeforeDelete($iBuilder);

        return true;
    }

    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        return $iSharQ->delete();
    }

    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        return $this->callAfterDelete($iBuilder, $result);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return null;
    }

    private function callBeforeDelete(ModelSharQ $iBuilder)
    {
        return $this->callStaticBeforeDelete($iBuilder);
    }

    private function callStaticBeforeDelete(ModelSharQ $iBuilder)
    {
        $args = StaticHookArguments::create($iBuilder);

        /** @var class-string<\Sharksmedia\Qarium\Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::beforeDelete($args);
    }

    private function callAfterDelete(ModelSharQ $builder, $result)
    {
        return $this->callStaticAfterDelete($builder, $result);
    }

    private function callStaticAfterDelete(ModelSharQ $iBuilder, $result)
    {
        $args = StaticHookArguments::create($iBuilder, $result);

        /** @var class-string<\Sharksmedia\Qarium\Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        $maybeResult = $modelClass::afterDelete($args);

        if ($maybeResult !== null)
        {
            return $maybeResult;
        }

        return $result;
    }
}
