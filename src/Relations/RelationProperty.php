<?php

declare(strict_types=1);

// 2023-07-10

namespace Sharksmedia\Objection\Relations;

use Sharksmedia\Objection\ReferenceBuilder;
use Sharksmedia\Objection\Exceptions\InvalidReferenceError;
use Sharksmedia\Objection\Exceptions\ModelNotFoundError;
use Sharksmedia\Objection\ModelQueryBuilder;

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
     * @var class-string<Model>
     */
    private string $modelClass = '';

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
    }

    /**
     * 2023-08-01
     * @param array<int, string> $references
     * @return array
     */
    private function createReferences(array $references): array
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

                $modelClass = $modelClassResolver($iReference->getTableName());

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

    /**
     * Returns an instance of ReferenceBuilder that points to the index:th value of a row.
     */
    public function ref(ModelQueryBuilder $iBuilder, int $index): ReferenceBuilder
    {
        $table = $iBuilder->getTableRefFor($this->modelClass);

        $ref = clone $this->references[$index];

        return $ref->table($table);
    }
}

