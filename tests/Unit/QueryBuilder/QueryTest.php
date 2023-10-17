<?php

declare(strict_types=1);

namespace Tests\Unit\SharQ;

use Codeception\Test\Unit;
use Sharksmedia\Qarium\Model;
use Sharksmedia\Qarium\ModelSharQ;
use Tests\Support\TQueryBuilder;

class QueryTest extends Unit
{
    use TQueryBuilder;

    public function testHasSelection(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $this->assertTrue($TestModel::query()->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->hasSelection('DifferentTable.foo'));

        $this->assertTrue($TestModel::query()->select('*')->hasSelection('DifferentTable.anything'));

        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelection('bar.anything'));
        $this->assertTrue($TestModel::query()->select('foo.*')->hasSelection('foo.anything'));
        
        $this->assertTrue($TestModel::query()->select('foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->select('foo')->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->select('foo')->hasSelection('DifferentTable.foo'));
        $this->assertFalse($TestModel::query()->select('foo')->hasSelection('bar'));

        $this->assertTrue($TestModel::query()->select('Model.foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->select('Model.foo')->hasSelection('Model.foo'));
        $this->assertFalse($TestModel::query()->select('Model.foo')->hasSelection('NotTestModel.foo'));
        $this->assertFalse($TestModel::query()->select('Model.foo')->hasSelection('bar'));

        $this->assertTrue($TestModel::query()->alias('t')->select('foo')->hasSelection('t.foo'));
        $this->assertTrue($TestModel::query()->alias('t')->select('t.foo')->hasSelection('foo'));
        $this->assertTrue($TestModel::query()->alias('t')->select('t.foo')->hasSelection('t.foo'));
        $this->assertFalse($TestModel::query()->alias('t')->select('foo')->hasSelection('Model.foo'));
    }


    public function testHasSelectionAs(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $this->assertTrue($TestModel::query()->hasSelectionAs('foo', 'foo'));
        $this->assertFalse($TestModel::query()->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('foo as bar')->hasSelectionAs('foo', 'bar'));
        
        $this->assertFalse($TestModel::query()->select('foo')->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('*')->hasSelectionAs('foo', 'foo'));
        
        $this->assertFalse($TestModel::query()->select('*')->hasSelectionAs('foo', 'bar'));
        
        $this->assertTrue($TestModel::query()->select('foo.*')->hasSelectionAs('foo.anything', 'anything'));
        
        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelectionAs('foo.anything', 'somethingElse'));
        
        $this->assertFalse($TestModel::query()->select('foo.*')->hasSelectionAs('bar.anything', 'anything'));
    }


    public function testHasSelectsShouldReturnTrueForAllVariantsOfSelectQueries(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $selects = [
            'select', 'columns', 'column', 'distinct', 'count',
            'countDistinct', 'min', 'max', 'sum', 'sumDistinct',
            'avg', 'avgDistinct'
        ];

        foreach ($selects as $name)
        {
            $query = $TestModel::query()->$name('arg');
            $this->assertTrue($query->hasSelects(), "TestModel::query()->$name('arg')->hasSelects()");
        }
    }


    public function testHasShouldMatchDefinedQueryOperations(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $operations = [
            'range', 'orderBy', 'limit', 'where', 'andWhere', 'whereRaw',
            'havingWrapped', 'rightOuterJoin', 'crossJoin', 'offset',
            'union', 'count', 'avg', 'with'
        ];

        foreach ($operations as $operation)
        {
            $query = $TestModel::query()->$operation('arg');

            foreach ($operations as $testOperation)
            {
                $this->assertEquals($testOperation === $operation, $query->has($testOperation), "TestModel::query()->$operation('arg')->has('$testOperation')");
                $this->assertEquals($testOperation === $operation, $query->has(preg_quote($testOperation, '/')), "TestModel::query()->$operation('arg')->has('/^$testOperation$/')");
            }
        }
    }


    public function testHasWheresShouldReturnTrueForAllVariantsOfWhereQueries(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $id     = null;
            public ?int $someId = null;

            public static $RelatedTestModelClass = null;

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'manyToManyRelation' => [
                        'relation'   => Model::MANY_TO_MANY_RELATION,
                        'modelClass' => static::$RelatedTestModelClass,
                        'join'       => [
                            'from'    => 'Model.id',
                            'through' => [
                                'from' => 'ModelRelation.someRelId',
                                'to'   => 'ModelRelation.someRelId',
                            ],
                            'to' => 'ModelRelation.someRelId',
                        ],
                    ],
                    'hasManyRelation' => [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => static::$RelatedTestModelClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'ModelRelation.someRelId',
                        ],
                    ],
                    'belongsToOneRelation' => [
                        'relation'   => Model::BELONGS_TO_ONE_RELATION,
                        'modelClass' => static::$RelatedTestModelClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId = null;

            public static function getTableName(): string
            {
                return 'ModelRelation';
            }
            public static function getTableIDs(): array
            {
                return ['someRelId'];
            }
        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;

        $this->assertFalse($TestModel::query()->hasWheres());
        $this->assertFalse($TestModel::query()->insert([])->hasWheres());
        $this->assertFalse($TestModel::query()->update([])->hasWheres());
        $this->assertFalse($TestModel::query()->patch([])->hasWheres());
        $this->assertFalse($TestModel::query()->delete()->hasWheres());

        $wheres = [
            'findOne', 'findById', 'where', 'andWhere', 'orWhere',
            'whereNot', 'orWhereNot', 'whereRaw', 'andWhereRaw', 'orWhereRaw',
            'whereWrapped', 'whereExists', 'orWhereExists', 'whereNotExists', 
            'orWhereNotExists', 'whereIn', 'orWhereIn', 'whereNotIn', 'orWhereNotIn',
            'whereNull', 'orWhereNull', 'whereNotNull', 'orWhereNotNull', 'whereBetween',
            'andWhereBetween', 'whereNotBetween', 'andWhereNotBetween', 'orWhereBetween',
            'orWhereNotBetween', 'whereColumn', 'andWhereColumn', 'orWhereColumn',
            'whereNotColumn', 'andWhereNotColumn', 'orWhereNotColumn'
        ];

        foreach ($wheres as $name)
        {
            $query = $TestModel::query()->$name(1, '=', 1);
            $this->assertTrue($query->hasWheres(), "TestModel::query()->$name()->hasWheres()");
        }

        $model = $TestModel::createFromDatabaseArray(['id' => 1, 'someId' => 1]);

        $query = $model->lquery();
        $this->assertTrue($query->hasWheres());

        $query = $model->lquery()->withGraphJoined('manyToManyRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('belongsToOneRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('hasManyRelation');
        $this->assertTrue($query->hasWheres());

        $query = $model->lrelatedQuery('manyToManyRelation');
        $this->assertTrue($query->hasWheres());
    }


    public function testHasWithGraphShouldReturnTrueForQueriesWithEagerStatements(): void
    {
        /** @var \Model $TestModel */
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?string $id = null;

            public static $RelatedTestModelClass = null;

            public static function getTableName(): string
            {
                return 'Model';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'someRel' => [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => static::$RelatedTestModelClass,
                        'join'       => [
                            'from' => 'Model.id',
                            'to'   => 'ModelRelation.someRelId',
                        ],
                    ],
                ];
            }
        };

        $RelatedTestModel = new class extends \Sharksmedia\Qarium\Model
        {
            protected ?string $someRelId = null;

            public static function getTableName(): string
            {
                return 'ModelRelation';
            }
            public static function getTableIDs(): array
            {
                return ['someRelId'];
            }
        };

        $TestModel::$RelatedTestModelClass = $RelatedTestModel::class;
        $query                             = $TestModel::query();
        $this->assertFalse($query->hasWithGraph());
        $query->withGraphJoined('someRel');
        $this->assertTrue($query->hasWithGraph());
        $query->clearWithGraph();
        $this->assertFalse($query->hasWithGraph());
    }


    public function testQueryUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $foo;

            public static function getTableName(): string
            {
                return 'person';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $query = $person::query()->update(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql   = $query->toFindQuery()->toString();

        $this->assertEquals('SELECT `person`.* FROM `person` WHERE `name` LIKE ?', $sql);
    }


    public function testRelatedQueryBelongsToOneUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $foo;
            
            public static function getTableName(): string
            {
                return 'person';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $pet = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $owner_id;

            public static $personClass;

            public static function getTableName(): string
            {
                return 'pet';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'owner' => [
                        'relation'   => self::BELONGS_TO_ONE_RELATION,
                        'modelClass' => self::$personClass,
                        'join'       => [
                            'from' => 'pet.owner_id',
                            'to'   => 'person.id'
                        ]
                    ]
                ];
            }
        };

        $pet::$personClass = $person::class;

        $instance = $pet::createFromDatabaseArray(['owner_id' => 1]);
        $query    = $instance->lrelatedQuery('owner')->patch(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql      = $query->toFindQuery()->toString();
        
        $this->assertEquals('SELECT `person`.* FROM `person` WHERE `person`.`id` IN(?) AND `name` LIKE ?', $sql);
    }


    public function testRelatedQueryFor(): void
    {
        $Person = new class extends Model
        {
            public static $Petclass;

            protected int $personID;
            protected array $iPets;

            protected static array $metadataCache =
            [
                'Persons' =>
                [
                    ['COLUMN_NAME' => 'personID']
                ]
            ];

            public static function getTableName(): string
            {
                return 'Persons';
            }
            public static function getTableIDs(): array
            {
                return ['personID'];
            }

            public static function getRelationMappings(): array
            {
                $relationsMap =
                [
                    'iParents' =>
                    [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => self::class,
                        'join'       =>
                        [
                            'from' => 'Persons.personID',
                            'to'   => 'Persons.parentID'
                        ]
                    ],
                    'iPets' =>
                    [
                        'relation'   => Model::HAS_MANY_RELATION,
                        'modelClass' => self::$Petclass,
                        'join'       =>
                        [
                            'from' => 'Persons.personID',
                            'to'   => 'Pets.ownerID'
                        ]
                    ]
                ];

                return $relationsMap;
            }
        };

        $Pet = new class extends Model
        {
            protected int $petID;
            protected int $ownerID;
            protected string $name;
            protected string $species;

            public static $foodClass;

            protected static array $metadataCache =
            [
                'Pets' =>
                [
                    ['COLUMN_NAME' => 'petID'],
                    ['COLUMN_NAME' => 'ownerID'],
                    ['COLUMN_NAME' => 'name'],
                    ['COLUMN_NAME' => 'species'],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Pets';
            }
            public static function getTableIDs(): array
            {
                return ['petID'];
            }

            public static function getRelationMappings(): array
            {
                $relations =
                [
                    'iFavoriteFood' =>
                    [
                        'relation'   => Model::BELONGS_TO_ONE_RELATION,
                        'modelClass' => self::$foodClass,
                        'join'       =>
                        [
                            'from' => 'Pets.favoriteFoodID',
                            'to'   => 'Foods.foodID'
                        ]
                    ]
                ];

                return $relations;
            }
        };

        $Food = new class extends Model
        {
            protected int $foodID;
            protected string $name;

            protected static array $metadataCache =
            [
                'Foods' =>
                [
                    ['COLUMN_NAME' => 'foodID'],
                    ['COLUMN_NAME' => 'name'],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Foods';
            }
            public static function getTableIDs(): array
            {
                return ['foodID'];
            }
        };

        $Person::$Petclass = $Pet::class;
        $Pet::$foodClass   = $Food::class;

        // Should be instance of Model at RelationOwner constructor

        $iDogsQuery = $Person::relatedQuery('iPets')
            ->for(1)
            ->where('species', 'Dog')
            ->orderBy('name', 'ASC');

        
        $iQuery = $iDogsQuery->toQuery();
        
        $this->assertEquals([
            'sql'      => 'SELECT `Pets`.* FROM `Pets` WHERE `Pets`.`ownerID` IN(?) AND `species` = ? ORDER BY `name` ASC',
            'bindings' => [1, 'Dog']
        ],
            [
                'sql'      => $iQuery->getSQL(),
                'bindings' => $iQuery->getBindings()
            ]);

        $iPerson = $Person::createFromDatabaseArray(['personID' => 1]);

        $iCatsQuery = $iPerson->lrelatedQuery('iPets')
            ->where('species', 'Cat')
            ->orderBy('name', 'ASC');
        
        $iQuery = $iCatsQuery->toQuery();

        $this->assertEquals([
            'sql'      => 'SELECT `Pets`.* FROM `Pets` WHERE `Pets`.`ownerID` IN(?) AND `species` = ? ORDER BY `name` ASC',
            'bindings' => [1, 'Cat']
        ],
            [
                'sql'      => $iQuery->getSQL(),
                'bindings' => $iQuery->getBindings()
            ]);

        $iDogsWithFoodQuery = $Person::relatedQuery('iPets')
            ->for(1)
            ->where('species', 'Dog')
            ->withGraphJoined('iFavoriteFood')
            ->orderBy('name', 'ASC');

        $iQuery = $iDogsWithFoodQuery->toQuery();

        $this->assertEquals([
            'sql'      => 'SELECT `Pets`.`petID` AS `petID`, `Pets`.`ownerID` AS `ownerID`, `Pets`.`name` AS `name`, `Pets`.`species` AS `species`, `iFavoriteFood`.`foodID` AS `iFavoriteFood:foodID`, `iFavoriteFood`.`name` AS `iFavoriteFood:name` FROM `Pets` LEFT JOIN `Foods` AS `iFavoriteFood` ON(`iFavoriteFood`.`foodID` = `Pets`.`favoriteFoodID`) WHERE `Pets`.`ownerID` IN(?) AND `species` = ? ORDER BY `name` ASC',
            'bindings' => [1, 'Dog']
        ],
            [
                'sql'      => $iQuery->getSQL(),
                'bindings' => $iQuery->getBindings()
            ]);

        $iCatsWithFoodQuery = $iPerson->lrelatedQuery('iPets')
            ->where('species', 'Cat')
            ->withGraphJoined('iFavoriteFood')
            ->orderBy('name', 'ASC');

        $iQuery = $iCatsWithFoodQuery->toQuery();

        $this->assertEquals([
            'sql'      => 'SELECT `Pets`.`petID` AS `petID`, `Pets`.`ownerID` AS `ownerID`, `Pets`.`name` AS `name`, `Pets`.`species` AS `species`, `iFavoriteFood`.`foodID` AS `iFavoriteFood:foodID`, `iFavoriteFood`.`name` AS `iFavoriteFood:name` FROM `Pets` LEFT JOIN `Foods` AS `iFavoriteFood` ON(`iFavoriteFood`.`foodID` = `Pets`.`favoriteFoodID`) WHERE `Pets`.`ownerID` IN(?) AND `species` = ? ORDER BY `name` ASC',
            'bindings' => [1, 'Cat']
        ],
            [
                'sql'      => $iQuery->getSQL(),
                'bindings' => $iQuery->getBindings()
            ]);

        $iPerson = $Person::query()
            ->withGraphJoined('[iParents, iPets]')
            ->modifyGraph('iParents', function ($iParentsQuery)
            {
                $iParentsQuery->where('name', 'John');
            })
            ->findById(1);

        $iQuery = $iPerson->toQuery();

        $this->assertEquals([
            'sql'      => 'SELECT `Persons`.`personID` AS `personID`, `iParents`.`personID` AS `iParents:personID`, `iPets`.`petID` AS `iPets:petID`, `iPets`.`ownerID` AS `iPets:ownerID`, `iPets`.`name` AS `iPets:name`, `iPets`.`species` AS `iPets:species` FROM `Persons` LEFT JOIN (SELECT `Persons`.* FROM `Persons` WHERE `name` = ?) AS `iParents` ON(`iParents`.`parentID` = `Persons`.`personID`) LEFT JOIN `Pets` AS `iPets` ON(`iPets`.`ownerID` = `Persons`.`personID`) WHERE `Persons`.`personID` = ?',
            'bindings' => ['John', 1]
        ],
            [
                'sql'      => $iQuery->getSQL(),
                'bindings' => $iQuery->getBindings()
            ]);
    }


    public function testRelatedQueryHasManyUpdate(): void
    {
        $person = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;

            public static $petClass;
            
            public static function getTableName(): string
            {
                return 'person';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }

            public static function getRelationMappings(): array
            {
                return [
                    'pets' => [
                        'relation'   => self::HAS_MANY_RELATION,
                        'modelClass' => self::$petClass,
                        'join'       => [
                            'from' => 'person.id',
                            'to'   => 'pet.owner_id'
                        ]
                    ]
                ];
            }
        };

        $pet = new class extends \Sharksmedia\Qarium\Model
        {
            public $id;
            public $owner_id;
            public $foo;

            public static function getTableName(): string
            {
                return 'pet';
            }
            public static function getTableIDs(): array
            {
                return ['id'];
            }
        };

        $person::$petClass = $pet::class;

        $instance = $person::createFromDatabaseArray(['id' => 1]);

        $query = $instance->lrelatedQuery('pets')->update(['foo' => 'bar'])->where('name', 'like', '%foo');
        $sql   = $query->toFindQuery()->toString();
        
        $this->assertEquals('SELECT `pet`.* FROM `pet` WHERE `pet`.`owner_id` IN(?) AND `name` LIKE ?', $sql);
    }


    public function testResultSizeShouldCreateAndExecuteAQueryThatReturnsTheSizeOfTheQuery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public ?int $a    = null;
            public ?int $b    = null;
            public ?int $test = null;

            protected static array $metadataCache =
            [
                'Model' =>
                [
                    [ 'COLUMN_NAME' => 'a', ],
                    [ 'COLUMN_NAME' => 'b', ],
                    [ 'COLUMN_NAME' => 'test' ],
                ]
            ];

            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $this->mockQueryResults = [[['count' => '123']]];
        
        $result = ModelSharQ::forClass($TestModel::class)
            ->where('test', 100)
            ->orderBy('order')
            ->limit(10)
            ->offset(100)
            ->resultSize();

        $this->assertCount(1, $this->executedQueries);
        $this->assertEquals(123, $result);
        $this->assertEquals(
            'SELECT COUNT(*) AS `count` FROM (SELECT `Model`.* FROM `Model` WHERE `test` = ?) AS `temp`',
            $this->executedQueries[0]['sql']
        );
    }


    public function testShouldCreateWhereInQueryForCompositeIdAndASubquery(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $subQuery = ModelSharQ::forClass($TestModel::class)
            ->select('a', 'b');

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], $subQuery)
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`,`B`.`b`) IN('.$subQuery->toSQL().')',
            $query
        );
    }


    public function testShouldCreateWhereInQueryForCompositeIdAndArrayOfChoices(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2], [3, 4]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?), (?, ?))',
            $query
        );
    }


    public function testShouldCreateWhereInQueryForCompositeIdAndSingleChoice(): void
    {
        $TestModel = new class extends \Sharksmedia\Qarium\Model
        {
            public static function getTableName(): string
            {
                return 'Model';
            }
        };

        $query = ModelSharQ::forClass($TestModel::class)
            ->whereInComposite(['A.a', 'B.b'], [[1, 2]])
            ->toSQL();

        $this->assertEquals(
            'SELECT `Model`.* FROM `Model` WHERE (`A`.`a`, `B`.`b`) IN((?, ?))',
            $query
        );
    }
}
