<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Qarium\Operations\DeleteOperation;
use Sharksmedia\Qarium\Operations\FindOperation;
use Sharksmedia\Qarium\ModelSharQOperationSupport;
use Sharksmedia\Qarium\Operations\InsertOperation;
use Sharksmedia\Qarium\Operations\UpdateOperation;
use Sharksmedia\Qarium\ModelSharQ;
use Sharksmedia\Qarium\ReferenceBuilder;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\Qarium;
use Sharksmedia\SharQ\SharQ;
use Sharksmedia\SharQ\Query;
use Tests\Support\MockMySQLClient;
use Sharksmedia\SharQ\Config;
use Tests\Support\MockPDOStatement;

trait TQueryBuilder
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected array $mockQueryResults = [];
    protected array $executedQueries  = [];

    protected function _before()
    {
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            ->database('testdb');

        $iClient = new MockMySQLClient($iConfig, function(Query $iQuery, array $options)
        {
            $sql      = $iQuery->getSQL();
            $bindings = $iQuery->getBindings();

            $iPDOStatement = new MockPDOStatement();

            $iPDOStatement->setResults(array_shift($this->mockQueryResults) ?? []);

            $this->executedQueries[] =
            [
                'sql'      => $sql,
                'bindings' => $bindings
            ];

            return $iPDOStatement;
        });

        $_GLOBAL['MockMySQLClient'] = $iClient;

        //
        // $iClient = Client::create($iConfig);

        $iClient->initializeDriver();

        Qarium::setClient($iClient);

        $iSharQ = new SharQ($iClient, 'db');

        Model::setSharQ($iSharQ);
        // You may initialize objects here if necessary

        $this->mockQueryResults = [];
        $this->executedQueries  = [];

        $iModelReflectionClass = new \ReflectionClass(Model::class);
        $iModelReflectionClass->setStaticPropertyValue('metadataCache', []);

        $iModelReflectionClass = new \ReflectionClass(Model::class);
        $iModelReflectionClass->setStaticPropertyValue('iRelationCache', []);

        parent::_before();
    }

    protected function _after()
    {
        $this->mockQueryResults = [];
        $this->executedQueries  = [];
        
        parent::_after();
    }

    // protected function setUp(): void
    // {
    //     $this->mockQueryResults = [];
    //     $this->executedQueries = [];
    // }

    public static function ref(string $expression): ReferenceBuilder
    {
        return new ReferenceBuilder($expression);
    }

    public static function createFindOperation(ModelSharQ $iBuilder, array $whereObj): FindOperation
    {
        $TestFindOperation = new class('find') extends FindOperation
        {
            public ?array $whereObject = null;

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool
            {
                return true;
            }

            public function onAfter2(ModelSharQOperationSupport $builder, &$result)
            {
                return $result;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                return $iSharQ->where($this->whereObject);
            }
        };

        $iFindOperation = new $TestFindOperation('find');

        $iFindOperation->whereObject = $whereObj;

        return $iFindOperation;
    }

    public static function createInsertOperation(ModelSharQ $iBuilder, array $whereObj): InsertOperation
    {
        $TestInsertOperation = new class('insert') extends InsertOperation
        {
            public array $insertData = [];

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool
            {
                return true;
            }

            public function onBefore3(ModelSharQOperationSupport $builder, ...$arguments): bool
            {
                return true;
            }

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
            {
                return $result;
            }

            public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
            {
                $this->iModels = [$arguments[0]];

                return true;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                $modelClass = $iBuilder->getModelClass();

                $iModel = $modelClass::ensureModel($this->iModels[0], $this->modelOptions);

                $this->iModels = [$iModel];
                
                $data = $this->iModels[0]->toDatabaseArray($iBuilder);

                $data = array_merge($data, $this->insertData);

                $modelClass = $iBuilder->getModelClass();
                
                $this->iModels[0] = $data;

                return $iSharQ->insert($this->iModels);
            }
        };

        $iInsertOperation = new $TestInsertOperation('insert');

        $iInsertOperation->insertData = $whereObj;

        return $iInsertOperation;
    }

    public static function createUpdateOperation(ModelSharQ $iBuilder, array $whereObj): UpdateOperation
    {
        $TestUpdateOperation = new class('update') extends UpdateOperation
        {
            public $testUpdateData = [];

            public function onBefore2(ModelSharQOperationSupport $builder, ...$arguments): bool
            {
                return true;
            }

            public function onBefore3(ModelSharQOperationSupport $builder, ...$arguments): bool
            {
                return true;
            }

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
            {
                return $result;
            }

            public function onAdd(ModelSharQOperationSupport $iBuilder, ...$arguments): bool
            {
                $data = $arguments[0];

                $modelClass = $iBuilder->getModelClass();

                $this->iModel = $modelClass::ensureModel($data, $this->modelOptions);

                return true;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                $data = $this->iModel->toDatabaseArray($iBuilder);

                $data = array_merge($data, $this->testUpdateData);

                $modelClass = $iBuilder->getModelClass();
                
                $this->iModel = $modelClass::ensureModel($data, $this->modelOptions);

                return $iSharQ->update($this->iModel);
            }
        };

        $iUpdateOperation = new $TestUpdateOperation('update');

        $iUpdateOperation->testUpdateData = $whereObj;

        return $iUpdateOperation;
    }

    public static function createDeleteOperation(ModelSharQ $iBuilder, array $whereObj): DeleteOperation
    {
        $TestDeleteOperation = new class('delete') extends DeleteOperation
        {
            public array $whereObject = [];

            public function onAfter2(ModelSharQOperationSupport $iBuilder, &$result)
            {
                return $result;
            }

            public function onBuildSharQ($iBuilder, $iSharQ)
            {
                return $iSharQ->delete()->where($this->whereObject);
            }
        };

        $iDeleteOperation = new $TestDeleteOperation('delete');

        $iDeleteOperation->whereObject = $whereObj;

        return $iDeleteOperation;
    }
}
