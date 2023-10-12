<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\JoinBuilder;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Statement\Join;

class SharQOperation extends QariumToSharQConvertingOperation
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

        $functionName = $this->getName();

        return $iSharQ->{$functionName}(...$this->getArguments($iBuilder));
    }
}
