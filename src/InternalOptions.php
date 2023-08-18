<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

class InternalOptions
{
    /**
     * @var bool
     */
    public $skipUndefined = false;

    /**
     * @var bool
     */
    public $keepImplicitJoinProps = false;

    /**
     * @var bool
     */
    public $isInternalQuery = false;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var string|null
     */
    public $schema = null;
}
