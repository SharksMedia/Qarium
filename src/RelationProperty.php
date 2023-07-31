<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-12

class RelationProperty
{
    private $refs;

    /**
     * 2023-07-12
     * @var class-string<Model>
     */
    private string $modelClass;

    /**
     * 2023-07-12
     * @var array
     */
    private array $props;

    /**
     * 2023-07-12
     * @var array
     */
    private array $cols;

    /**
     * 2023-07-12
     * @var array
     */
    private $propGetters;

    /**
     * 2023-07-12
     * @var array
     */
    private $propSetters;

    /**
     * 2023-07-12
     * @var array
     */
    private $patchers;

    public function __construct($references, \Closure $modelClassResolver)
    {
        if(!is_array($references)) $references = [$references];

        $refs = self::createRefs($references);
        $paths = self::createPaths($refs, $modelClassResolver);
        $modelClass = $modelClassResolver();

        $this->refs = array_map(function($ref) use($modelClass){ return $ref->model($modelClass); }, $refs);
        $this->modelClass = $modelClass;
        $this->props = array_map(function($path){ return $path[0]; }, $paths);
        $this->cols = array_map(function($ref){ return $ref->getColumnName(); }, $refs);
        $this->propGetters = array_map(function($path){ return self::createGetter($path->path); }, $paths);
        $this->propSetters = array_map(function($path){ return self::createSetter($path->path); }, $paths);
        $this->propSetters = array_map(function($ref, $i){ return self::createPatcher($ref, $i); }, $refs, array_keys($refs));
    }

    private static function createRefs(array $references)
    {
        try
        {
            return array_map(function($it)
                {
                    if(!is_object($it) || !($it instanceof ReferenceBuilder)) return self::createRef($it);

                    return $it;
                }, $references);

        }
        catch(\Exception $e)
        {
            throw new InvalidReferenceError($e->getMessage(), $e->getCode(), $e);
        }
    }

    private static function createRef($reference)
    {
        return ReferenceBuilder::ref($reference);
    }

    /**
     * 2023-07-12
     * @param array<int, ReferenceBuilder> $references
     * @param \Closure $modelClassResolver
     * @return array
     * @throws InvalidReferenceError
     * @throws ModelNotFoundError
     */
    private static function createPaths(array $references, \Closure $modelClassResolver)
    {
        return array_map(function($reference) use($modelClassResolver)
            {
                if($reference->getTableName() === null) throw new InvalidReferenceError('Invalid reference: no table name');

                $modelClass = $modelClassResolver($reference->getTableName());

                if($modelClass === null) throw new ModelNotFoundError('Model not found for table: ' . $reference->getTableName());

                // $prop = $modelClass::columnNameToPropertyName($reference->getColumnName());
                $prop = $reference->getColumnName();
                $jsonPath = array_map(function($ref){ return $ref->ref; }, $reference->parsedExpr->access);

                return [
                    'path'=>array_merge([$prop], $jsonPath),
                    'modelClass'=>$modelClass,
                ];
            }, $references);
    }

    private static function createGetter(array $path)
    {
        if(count($path) === 1)
        {
            $prop = $path[0];
            return function($model) use($prop){ return $model->$prop; };
        }

        return function($model) use($path){ return self::get($model, $path); };
    }

}
