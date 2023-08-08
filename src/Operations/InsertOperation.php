<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection\Operations;

use Sharksmedia\Objection\Model;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\Objection\ModelQueryBuilderOperationSupport;
use Sharksmedia\Objection\StaticHookArguments;

class InsertOperation extends ModelQueryBuilderOperation
{
    protected array $iModels;
    protected bool $isArray;
    protected array $modelOptions;

    public function __construct(string $name, array $options=[])
    {
        parent::__construct($name, $options);

        $this->iModels = [];
        $this->isArray = false;
        $this->modelOptions = array_merge([], $this->options['modelOptions'] ?? []);
    }

    public function onAdd(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        $array = $arguments[0];
        $modelClass = $iBuilder->getModelClass();

        $this->isArray = is_array($array);
        $this->iModels = $modelClass::ensureModelArray($array, $this->modelOptions);

        return true;
    }

    public function onBefore2(ModelQueryBuilderOperationSupport $iBuilder, ...$arguments): bool
    {
        if(count($this->iModels) === 0) throw new \Exception('Batch insert only works with Postgresql and SQL Server');

        self::callBeforeInsert($iBuilder, $this->iModels);

        return true;
        // return $arguments;
    }

    public function onBuildQueryBuilder(ModelQueryBuilderOperationSupport $iBuilder, $iQueryBuilder)
    {
        return $iQueryBuilder->insert(array_map(function(Model $iModel) use($iBuilder)
        {
            $data = $iModel->toDatabaseArray($iBuilder);

            return $data;
        }, $this->iModels));
    }

    public function onAfter1(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        if(!is_array($result) || count($result) === 0 || $result === $this->iModels)
        {
            // Early exit if there is nothing to do.
            return $this->iModels;
        }

    // if (isObject(ret[0])) {
    //   // If the user specified a `returning` clause the result may be an array of objects.
    //   // Merge all values of the objects to our models.
    //   for (let i = 0, l = this.models.length; i < l; ++i) {
    //     this.models[i].$setDatabaseJson(ret[i]);
    //   }
    //     }

        // If the return value is not an array of objects, we assume it is an array of identifiers.
        foreach($this->iModels as $i=>&$iModel)
        {
            // Don't set the id if the model already has one. MySQL and Sqlite don't return the correct
            // primary key value if the id is not generated in db, but given explicitly.
            if($iModel->getID() === null) $iModel->setID($result[$i]);
        }

        return $this->iModels;
    }

    public function onAfter2(ModelQueryBuilderOperationSupport $iBuilder, &$result)
    {
        $result = $this->isArray
            ? $this->iModels
            : $this->iModels[0] ?? null;

        return self::callAfterInsert($iBuilder, $this->iModels, $result);
    }

    public function toFindOperation(ModelQueryBuilderOperationSupport $iBuilder): ?ModelQueryBuilderOperation
    {
        return null;
    }

    private static function callBeforeInsert(ModelQueryBuilder $iBuilder, array $iModels)
    {
        foreach($iModels as $iModel)
        {
            $iModel->beforeInsert($iBuilder->getContext());
        }

        $modelClass = $iBuilder->getModelClass();

        $arguments = StaticHookArguments::create($iBuilder);
        $modelClass::beforeInsert($arguments);
    }

    private static function callAfterInsert(ModelQueryBuilder $iBuilder, array $iModels, $iResult)
    {
        foreach($iModels as $iModel)
        {
            $iModel->afterInsert($iBuilder->getContext());
        }

        $modelClass = $iBuilder->getModelClass();

        $arguments = StaticHookArguments::create($iBuilder, $iResult);
        $result = $modelClass::afterInsert($arguments);

        return $result;
    }






}
