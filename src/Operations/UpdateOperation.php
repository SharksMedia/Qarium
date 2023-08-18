<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\StaticHookArguments;
use Sharksmedia\Qarium\ModelSharQ;

class UpdateOperation extends ModelSharQOperation
{

    protected ?Model $iModel = null;
    protected array $updateData = [];
    protected $modelOptions = [];

    public function __construct($name, $options=[])
    {
        parent::__construct($name, $options);

        $this->modelOptions = array_merge([], $this->options['modelOptions'] ?? []);
    }

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $data = $arguments[0];

        if(is_array($data))
        {
            $this->updateData = $data;
        }
        else if($data instanceof Model)
        {
            $this->updateData = [];
        }
        else
        {
            throw new \Exception("Invalid data type for update operation.");
        }


        /** @var \Model $modelClass */
        $modelClass = $iBuilder->getModelClass();

        $this->iModel = $modelClass::ensureModel($data, $this->modelOptions);

        return true;
    }

    public function onBefore2(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->callBeforeUpdate($iBuilder, $this->iModel, $this->modelOptions);

        return true;
    }

    /**
     * @param ModelSharQOperationSupport|ModelSharQ $iBuilder
     * @param mixed ...$arguments
     * @return bool
     */
    public function onBefore3(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        if($this->iModel === null) return true;
        
        $row = $this->iModel->toDatabaseArray($iBuilder);

        if(count($row) === 0)
        {
            // Resolve the query if there is nothing to update.
            $iBuilder->resolve(0);
        }

        return true;
    }

    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        $json = $this->iModel->toDatabaseArray($iBuilder);

        $convertedJson = $this->convertFieldExpressionsToRaw($iBuilder, $this->iModel, $json);

        return $iSharQ->update(array_merge($convertedJson, $this->updateData));
    }

    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        return $this->callAfterUpdate($iBuilder, $this->iModel, $this->modelOptions, $result);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return null;
    }

    private function callBeforeUpdate(ModelSharQ $iBuilder, ?Model $iModel, $modelOptions)
    {
        $this->callInstanceBeforeUpdate($iBuilder, $iModel, $modelOptions);

        return $this->callStaticBeforeUpdate($iBuilder);
    }

    private function callInstanceBeforeUpdate(ModelSharQ $iBuilder, ?Model $iModel, $modelOptions)
    {
        if($iModel === null) return null;

        return $iModel->lbeforeUpdate($iBuilder->getContext());
    }

    private function callStaticBeforeUpdate(ModelSharQ $iBuilder)
    {
        $args = StaticHookArguments::create($iBuilder);

        /** @var \Model $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::beforeUpdate($args);
    }

    private function callAfterUpdate(ModelSharQ $iBuilder, ?Model $iModel, $modelOptions, $result)
    {
        $this->callInstanceAfterUpdate($iBuilder, $iModel, $modelOptions);
        return $this->callStaticAfterUpdate($iBuilder, $result);
    }

    private function callInstanceAfterUpdate(ModelSharQ $iBuilder, $iModel, $modelOptions)
    {
        return $iModel->lafterUpdate($iBuilder->getContext());
    }

    private function callStaticAfterUpdate(ModelSharQ $iBuilder, $result)
    {
        $args = StaticHookArguments::create($iBuilder, $result);

        /** @var \Model $modelClass */
        $modelClass = $iBuilder->getModelClass();

        $maybeResult = $modelClass::afterUpdate($args);

        return $maybeResult !== null ? $maybeResult : $result;
    }

    private function convertFieldExpressionsToRaw(ModelSharQ $iBuilder, ?Model $iModel, array $json): array
    {
        // You need to implement or find a suitable library for ref() function or its equivalent in PHP.
        // Similar changes will be required for isKnexSharQ() and isKnexRaw() functions.

        $iSharQ = $iBuilder->getSharQ();
        $convertedJson = [];

        foreach ($json as $key => $val) {
            if (strpos($key, ':') !== false) {
                // The equivalent to JavaScript ref() function needs to be implemented
                // $parsed = ref($key);
                // $jsonRefs = '{' . join(',', $parsed['parsedExpr']['access']) . '}';
                // $valuePlaceholder = '?';

                // if (isKnexSharQ($val) || isKnexRaw($val)) {
                //     $valuePlaceholder = 'to_jsonb(?)';
                // } else {
                //     $val = json_encode($val);
                // }

                // $convertedJson[$parsed['column']] = $iSharQ->raw(
                //     `jsonb_set(??, '${jsonRefs}', ${valuePlaceholder}, true)`,
                //     [$convertedJson[$parsed['column']] ?? $parsed['column'], $val]
                // );

                // unset($iModel[$key]);
            } else {
                $convertedJson[$key] = $val;
            }
        }

        return $convertedJson;
    }
}
