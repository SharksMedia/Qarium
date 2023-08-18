<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Qarium\Relations;

use Sharksmedia\Qarium\ReferenceBuilder;
use Sharksmedia\Qarium\Exceptions\InvalidReferenceError;
use Sharksmedia\Qarium\Exceptions\ModelNotFoundError;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\Utilities;

/**
 * A pair of these define how two tables are related to each other.
 * Both the owner and the related table have one of these.
 *
 * A relation property can be a single column, an array of columns
 * (composite key) a json column reference, an array of json column
 * references or any combination of the above.
 */
class RelationProperty
{
    private const PROP_KEY_PREFIX = 'k_';

    /**
     * 2023-08-01
     * @var array
     */
    private array $columns = [];

    /**
     * 2023-08-01
     * @var array
     */
    private array $properties = [];

    /**
     * 2023-08-01
     * @var array<int, ReferenceBuilder>
     */
    private array $references = [];

    /**
     * 2023-08-01
     * @var class-string<Model> $modelClass
     */
    private string $modelClass = '';

    /**
     * 2023-08-01
     * @var array<int, \Closure> $propGetters
     */
    private array $propGetters = [];

    /**
     * 2023-08-01
     * @var array<int, \Closure> $propSetters
     */
    private array $propSetters = [];

    /**
     * 2023-08-01
     * @var array<int, \Closure> $propSetters
     */
    private array $patchers = [];

    private array $propCheckers;

    /**
     * references must be a reference string like `Table.column:maybe.some.json[1].path`.
     * or an array of such references (composite key).
     *
     * modelClassResolver must be a function that takes a table name
     * and returns a model class.
     *
     * @param string|array<int, string> $references
     * @param \Closure $modelClassResolver
     */
    public function __construct($references, \Closure $modelClassResolver)
    {
        $refs = $this->createReferences(is_array($references) ? $references : [$references]);
        $paths = $this->createPaths($refs, $modelClassResolver);
        $modelClass = $this->resolveModelClass($paths);

        $this->references = array_map(function($ref) use ($modelClass)
            {
                return $ref->model($modelClass);
            }, $refs);

        $this->modelClass = $modelClass;

        $this->properties = array_map(function($it)
            {
                return $it->path[0];
            }, $paths);

        $this->columns = array_map(function($it)
            {
                return $it->getColumn();
            }, $refs);

        $this->propGetters = array_map(function($it)
            {
                return self::createGetter($it->path);
            }, $paths);

        $this->propSetters = array_map(function($it)
            {
                return self::createSetter($it->path);
            }, $paths);

        $this->propCheckers = array_map(function($it)
            {
                return self::createChecker($it->path);
            }, $paths);

        $this->patchers = array_map(function($it, $path)
            {
                return self::createPatcher($it, $path->path);
            }, $refs, $paths);
        


    // this._propGetters = paths.map((it) => createGetter(it.path));
    // this._propSetters = paths.map((it) => createSetter(it.path));
    // this._patchers = refs.map((it, i) => createPatcher(it, paths[i].path));


    }

    private static function createGetter(array $path): \Closure
    {
        if(count($path) === 1)
        {
            $prop = $path[0];

            return function(&$obj) use ($prop)
            {
                return $obj[$prop];
            };
        }

        return function(&$obj) use ($path)
        {
            return Utilities::get($obj, $path);
        };
    }

    private static function createSetter(array $path): \Closure
    {
        if(count($path) === 1)
        {
            $prop = $path[0];

            return function(&$obj, $value) use ($prop)
            {
                $obj[$prop] = $value;
            };
        }

        return function(&$obj, $value) use ($path)
        {
            Utilities::set($obj, $path, $value);
        };
    }

    private static function createPatcher(ReferenceBuilder $ref, array $path): \Closure
    {
        if($ref->isPlainColumnRef())
        {
            return function(&$patch, $value) use ($path)
            {
                $patch[$path[0]] = $value;
            };
        }

        // Qarium `patch`, `update` etc. methods understand field expressions.
        return function(&$patch, $value) use ($ref)
        {
            $patch[$ref->getExpression()] = $value;
        };
    }

    private static function createChecker(array $path): \Closure
    {
        if(count($path) === 1)
        {
            $prop = $path[0];

            return function(&$obj) use ($prop)
            {
                return array_key_exists($prop, $obj);
            };
        }

        return function(&$obj) use ($path)
        {
            return Utilities::has($obj, $path);
        };
    }

    /**
     * 2023-08-01
     * @param array<int, string> $references
     * @return array
     */
    public function createReferences(array $references): array
    {// 2023-08-01
        try
        {
            $refs = [];
            foreach($references as $reference)
            {
                if(!is_object($reference) || !is_subclass_of($reference, ReferenceBuilder::class))
                {
                    // $reference = is_array($reference) ? $reference : [$reference];

                    $refs[] = $this->createReference($reference);
                }
                else
                {
                    $refs[] = $reference;
                }
            }

            return $refs;
        }
        catch(\Exception $error)
        {
            throw new InvalidReferenceError();
        }
    }

    private function createReference($reference): ReferenceBuilder
    {
        if(is_object($reference) && is_subclass_of($reference, ReferenceBuilder::class)) return $reference;

        return new ReferenceBuilder($reference);
    }

    /**
     * 2023-08-01
     * @param array<int, ReferenceBuilder> $iReferences
     * @param Closure $modelClassResolver
     * @return array<string, object>
     */
    private function createPaths(array $iReferences, \Closure $modelClassResolver): array
    {
        return array_map(function(ReferenceBuilder $iReference) use ($modelClassResolver)
            {
                if($iReference->getTableName() === null) throw new InvalidReferenceError();

                /** @var Model $modelClass */
                $modelClass = $modelClassResolver($iReference->getTableName(), $iReference->getColumn());

                if(!$modelClass) throw new ModelNotFoundError($iReference->getTableName());

                $prop = $modelClass::columnNameToPropertyName($iReference->getColumn());

                $jsonPath = array_map(function($it)
                    {
                        return $it->iReference;
                    }, $iReference->getParsedExpression()->access);

                $path = (object)
                [
                    'path'=>array_merge([$prop], $jsonPath),
                    'modelClass'=>$modelClass,
                ];

                return $path;
            }, $iReferences);
    }

    /**
     * 2023-08-01
     * @param array<int, string> $paths
     * @return string
     */
    private function resolveModelClass(array $paths): string
    {
        $modelClasses = array_map(function($it)
            {
                return $it->modelClass;
            }, $paths);

        $uniqueModelClasses = array_unique($modelClasses, SORT_REGULAR);

        if(count($uniqueModelClasses) !== 1) throw new InvalidReferenceError();

        return $modelClasses[0];
    }

    public function getColumns(): array
    {// 2023-08-01
        return $this->columns;
    }

    public function getProperties(): array
    {// 2023-08-01
        return $this->properties;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function getSize(): int
    {
        return count($this->references);
    }

    /**
     * Returns an instance of ReferenceBuilder that points to the index:th value of a row.
     */
    public function ref(ModelSharQ $iBuilder, int $index): ReferenceBuilder
    {
        $table = $iBuilder->getTableRefFor($this->modelClass);

        $ref = clone $this->references[$index];

        return $ref->table($table);
    }

    public function refs(ModelSharQ $iBuilder): array
    {
        $refs = [];

        foreach($this->references as $index=>$reference)
        {
            $refs[] = $this->ref($iBuilder, $index);
        }

        return $refs;
    }

    // Appends an update operation for the index:th column into `patch` object.
    public function patch(&$patch, int $index, $value)
    {
        $patcher = $this->patchers[$index] ?? null;

        if(!$patcher) throw new \Exception('No patcher for index: '.$index);

        return $patcher($patch, $value);
    }

    // Returns the index:th property value of the given object.
    public function setProp(&$obj, int $index, $value)
    {
        $setter = $this->propSetters[$index] ?? null;

        if(!$setter) throw new \Exception('No setter for index: '.$index);

        return $setter($obj, $value);
    }

    // Returns the index:th property value of the given object.
    public function getProp(&$obj, int $index)
    {
        $getter = $this->propGetters[$index] ?? null;

        if(!$getter) throw new \Exception('No getter for index: '.$index);

        return $getter($obj);
    }

    // Returns the property values of the given object as an array.
    public function getProps(&$obj)
    {
        $props = [];

        foreach($this->propGetters as $getter)
        {
            $props[] = $getter($obj);
        }

        return $props;
    }

    public function getPropKey(&$obj)
    {
        $size = $this->getSize();
        $key = self::PROP_KEY_PREFIX;

        for($i = 0; $i < $size; ++$i)
        {
            $key .= self::propToStr($this->getProp($obj, $i));

            if($i < $size - 1) $key .= ',';
        }

        return $key;
    }

    private static function propToStr($value)
    {
        if($value === null) return 'null';
        if(is_object(($value))) return json_encode($value);

        return $value.'';
    }

    public function hasProp(&$obj, int $index): bool
    {
        $checker = $this->propCheckers[$index] ?? null;

        if(!$checker) throw new \Exception('No getter for index: '.$index);

        return $checker($obj);
    }

    // String representation of this property's index:th column for logging.
    public function getPropDescription(int $index): ?string
    {
        $iReference = $this->references[$index];

        return $iReference->getExpression();
    }
}

