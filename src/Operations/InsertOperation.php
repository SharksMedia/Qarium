<?php

/**
 * 2023-07-10
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium\Operations;

use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\StaticHookArguments;

class InsertOperation extends ModelSharQOperation
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

    public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        $array = $arguments[0];
        $modelClass = $iBuilder->getModelClass();

        $this->isArray = is_array($array);
        $this->iModels = $modelClass::ensureModelArray($array, $this->modelOptions);

        return true;
    }

    public function onBefore2(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
    {
        if(count($this->iModels) > 1) throw new \Exception('Batch insert only works with Postgresql and SQL Server');

        self::callBeforeInsert($iBuilder, $this->iModels);

        return true;
        // return $arguments;
    }

    public function onBuildSharQ(ModelSharQOperationSupport $iBuilder, $iSharQ)
    {
        return $iSharQ->insert(array_map(function(Model $iModel) use($iBuilder)
        {
            $data = $iModel->toDatabaseArray($iBuilder);

            return $data;
        }, $this->iModels));
    }

    public function onAfter1(ModelSharQOperationSupport $iBuilder, &$result)
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

    public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
    {
        $result = $this->isArray
            ? $this->iModels
            : $this->iModels[0] ?? null;

        return self::callAfterInsert($iBuilder, $this->iModels, $result);
    }

    public function toFindOperation(ModelSharQOperationSupport $iBuilder): ?ModelSharQOperation
    {
        return null;
    }

    /**
     * @param ModelSharQ $iBuilder
     * @param Model[] $iModels
     */
    private static function callBeforeInsert(ModelSharQ $iBuilder, array $iModels)
    {
        foreach($iModels as $iModel)
        {
            $iModel->lbeforeInsert($iBuilder->getContext());
        }

        $modelClass = $iBuilder->getModelClass();

        $arguments = StaticHookArguments::create($iBuilder);
        $result = $modelClass::beforeInsert($arguments);

        return $result;
    }

    private static function callAfterInsert(ModelSharQ $iBuilder, array $iModels, $iResult)
    {
        foreach($iModels as $iModel)
        {
            $iModel->lafterInsert($iBuilder->getContext()->userContext);
        }

        $modelClass = $iBuilder->getModelClass();

        $arguments = StaticHookArguments::create($iBuilder, $iResult);
        $result = $modelClass::afterInsert($arguments);

        return $result;
    }






}
