<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Join;

class FirstOperation extends ModelSharQOperation
{
    /**
     * @param ModelSharQ|ModelSharQOperationSupport $iBuilder
     * @param SharQ|Join|null $iSharQ
     * @return SharQ|Join|null
     */
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        if ($iSharQ !== null && !($iSharQ instanceof SharQ) && !($iSharQ instanceof Join))
        {
            throw new \Exception('Invalid SharQ type: '.get_class($iSharQ));
        }

        $modelClass = $iBuilder->getModelClass();

        if ($iBuilder->isFind() && $modelClass::USE_LIMIT_IN_FIRST)
        {
            $iSharQ->limit(1);
        }

        return $iSharQ;
    }

    /**
     * @param array|Model|null $result
     * @return array|Model|null
     */
    public function onAfter3(ModelSharQOperationSupport $iBuilder, &$result)
    {
        if (is_array($result))
        {
            return reset($result);
        }

        return $result;
    }
}


