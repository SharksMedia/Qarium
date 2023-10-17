<?php

/**
 * 2023-07-11
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\ModelSharQOperationSupport;

class InsertAndFetchOperation extends DelegateOperation
{
    public function __construct(string $name, array $options)
    {
        parent::__construct($name, $options);

        if ($this->delegate->is('InsertOperation'))
        {
            throw new \Exception('InsertAndFetchOperation cannot delegate to InsertOperation');
        }
    }

    public function getModels(): array
    {
        return $this->delegate->getModels();
    }

    /**
     * @param ModelSharQOperationSupport $iBuilder
     * @param array|Sharksmedia\Qarium\Operations\Model|null $result
     * @return array|Sharksmedia\Qarium\Operations\Model|null
     */
    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        /** @var class-string<\Sharksmedia\Qarium\Model> $modelClass */
        $modelClass     = $iBuilder->getModelClass();
        $insertedModels = parent::onAfter2($iBuilder, $result);

        // $insertedModelArray = $modelClass::ensureModelArray($insertedModels);
        $insertedModelsArray = is_array($insertedModels) ? $insertedModels : [$insertedModels];
        $idProps             = $modelClass::getTableIDs();
        $ids                 = $modelClass::getIdsFromModels($insertedModelsArray);

        $fetchedModels = $modelClass::query()
            ->childQueryOf($iBuilder)
            ->findByIds($ids)
            ->castTo($modelClass)
            ->run();

        // $modelsById = [];

        return $fetchedModels;
    }
}
