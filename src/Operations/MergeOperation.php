<?php

/**
 * 2023-07-07
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQ;
use Exception;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\OnConflictBuilder;

class MergeOperation extends ModelSharQOperation
{
    /**
     * @var null
     */
    private $model;

    /**
     * @var array
     */
    private array $arguments;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->model     = null;
        $this->arguments = [];
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->arguments = $arguments;

        if (count($arguments) !== 0 && is_object($arguments[0]))
        {
            $data       = $arguments[0];
            $modelClass = $iBuilder->getModelClass();

            $this->model = $modelClass::ensureModel($data, ['patch' => true]);
        }

        return true;
    }

    /**
     * @param ModelSharQ|ModelSharQOperationSupport $iBuilder
     * @param OnConflictBuilder|SharQ|Sharksmedia\Qarium\Operations\Join|null $iSharQ
     * @return SharQ|Sharksmedia\Qarium\Operations\Join|null
     * @throws Exception
     */
    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ): ?SharQ
    {
        if (!method_exists($iSharQ, 'merge'))
        {
            throw new \Exception('SharQ merge is not callable');
        }

        if ($this->model)
        {
            $data          = $this->model->toDatabaseArray($iBuilder);
            $convertedData = UpdateOperation::convertFieldExpressionsToRaw($iBuilder, $this->model, $data);

            return $iSharQ->merge($convertedData);
        }

        return $iSharQ->merge(...$this->arguments);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return null;
    }
}
