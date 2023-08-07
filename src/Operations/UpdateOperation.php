<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\Objection\StaticHookArguments;
use Sharksmedia\Objection\ModelQueryBuilder;

class UpdateOperation extends ModelQueryBuilderOperation
{

    private $model = null;
    private $modelOptions = [];

    public function __construct($name, $options=[])
    {
        parent::__construct($name, $options);

        $this->modelOptions = array_merge([], $this->options['modelOptions'] ?? []);
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $json = $arguments[0];
        $modelClass = $iBuilder->getModelClass();

        $this->model = $modelClass::ensureModel($json, $this->modelOptions);

        return true;
    }

    public function onBefore2(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $this->callBeforeUpdate($iBuilder, $this->model, $this->modelOptions);

        return true;
    }

    public function onBefore3(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $row = $this->model->toDatabaseJson($iBuilder);

        if(empty($row))
        {
            // Resolve the query if there is nothing to update.
            $iBuilder->resolve(0);
        }

        true;
    }

    public function onBuildQueryBuilder(ModelQueryBuilder $iBuilder, $iQueryBuilder)
    {
        $json = $this->model->toDatabaseJson($iBuilder);
        $convertedJson = $this->convertFieldExpressionsToRaw($iBuilder, $this->model, $json);

        return $iQueryBuilder->update($convertedJson);
    }

    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        return $this->callAfterUpdate($iBuilder, $this->model, $this->modelOptions, $result);
    }

    public function toFindOperation(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperation
    {
        return null;
    }

    private function callBeforeUpdate(ModelQueryBuilder $iBuilder, $model, $modelOptions)
    {
        $this->callInstanceBeforeUpdate($iBuilder, $model, $modelOptions);
        return $this->callStaticBeforeUpdate($iBuilder);
    }

    private function callInstanceBeforeUpdate(ModelQueryBuilder $iBuilder, $model, $modelOptions)
    {
        return $model->beforeUpdate($modelOptions, $iBuilder->getContext());
    }

    private function callStaticBeforeUpdate(ModelQueryBuilder $iBuilder)
    {
        $args = StaticHookArguments::create($iBuilder);

        /** @var class-string<Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        return $modelClass::beforeUpdate($args);
    }

    private function callAfterUpdate(ModelQueryBuilder $iBuilder, $model, $modelOptions, $result)
    {
        $this->callInstanceAfterUpdate($iBuilder, $model, $modelOptions);
        return $this->callStaticAfterUpdate($iBuilder, $result);
    }

    private function callInstanceAfterUpdate(ModelQueryBuilder $iBuilder, $model, $modelOptions)
    {
        return $model->afterUpdate($modelOptions, $iBuilder->getContext());
    }

    private function callStaticAfterUpdate(ModelQueryBuilder $iBuilder, $result)
    {
        $args = StaticHookArguments::create($iBuilder, $result);

        /** @var class-string<Model> $modelClass */
        $modelClass = $iBuilder->getModelClass();

        $maybeResult = $modelClass::afterUpdate($args);

        return $maybeResult !== null ? $maybeResult : $result;
    }

    private function convertFieldExpressionsToRaw(ModelQueryBuilder $iBuilder, $model, array $json)
    {
        // You need to implement or find a suitable library for ref() function or its equivalent in PHP.
        // Similar changes will be required for isKnexQueryBuilder() and isKnexRaw() functions.

        $iQueryBuilder = $iBuilder->getQueryBuilder();
        $convertedJson = [];

        foreach ($json as $key => $val) {
            if (strpos($key, ':') !== false) {
                // The equivalent to JavaScript ref() function needs to be implemented
                // $parsed = ref($key);
                // $jsonRefs = '{' . join(',', $parsed['parsedExpr']['access']) . '}';
                // $valuePlaceholder = '?';

                // if (isKnexQueryBuilder($val) || isKnexRaw($val)) {
                //     $valuePlaceholder = 'to_jsonb(?)';
                // } else {
                //     $val = json_encode($val);
                // }

                // $convertedJson[$parsed['column']] = $iQueryBuilder->raw(
                //     `jsonb_set(??, '${jsonRefs}', ${valuePlaceholder}, true)`,
                //     [$convertedJson[$parsed['column']] ?? $parsed['column'], $val]
                // );

                // unset($model[$key]);
            } else {
                $convertedJson[$key] = $val;
            }
        }

        return $convertedJson;
    }
}
