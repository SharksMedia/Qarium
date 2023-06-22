<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;
use Tests\Support\Person;
use Tests\Support\School;
use Tests\Support\Student;
use Tests\Support\ModelTester;

class ModelCreationTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected static function getClient(): Client
    {// 2023-06-15
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            ->host('127.0.0.1')
            ->port('3306')
            ->user('user')
            ->password('password')
            ->database('db')
            ->charset('utf8mb4');

        $iClient = Client::create($iConfig); // MySQL client

        $iClient->initializeDriver();

        return $iClient;
    }

    /**
     * Method is called before test file run
     */
    protected function _before(): void
    {// 2023-06-15
    }

    /**
     * @return array<string, array<int, ModelQueryBuilder|array>>
     */
    public function caseProvider(): array
    {// 2023-05-16
        // Providers are run before anything else, so we are initalizing the client here.
        Objection::setClient(self::getClient());

        $iClassPerson = new \ReflectionClass(Person::class);
        $iPropPersonID = $iClassPerson->getProperty('personID');
        $iPropParentID = $iClassPerson->getProperty('parentID');
        $iPropName = $iClassPerson->getProperty('name');
        $iPropIChildren = $iClassPerson->getProperty('iChildren');
        $iPropIParents = $iClassPerson->getProperty('iParents');
        
        $iClassSchool = new \ReflectionClass(School::class);
        $iPropSchoolID = $iClassSchool->getProperty('schoolID');
        $iPropName = $iClassSchool->getProperty('name');
        $iPropIStudents = $iClassSchool->getProperty('iStudents');

        $iClassStudent = new \ReflectionClass(Student::class);
        $iPropPersonID = $iClassStudent->getProperty('personID');
        $iPropParentID = $iClassStudent->getProperty('parentID');
        $iPropName = $iClassStudent->getProperty('name');
        $iPropIChildren = $iClassStudent->getProperty('iChildren');
        $iPropIParents = $iClassStudent->getProperty('iParents');


        foreach([$iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren] as $iProp)
        {
            $iProp->setAccessible(true);
        }

        $cases = [];

        $cases['Basic with graph joined, find by id, no relations available'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren)
        {
            $iPerson = new Person();
            $iPropPersonID->setValue($iPerson, 3);
            $iPropParentID->setValue($iPerson, null);
            $iPropName->setValue($iPerson, 'Magnus');
            // $iPropIChildren->setValue($iPerson, []);

            $iChild1 = new Person();
            $iPropPersonID->setValue($iChild1, 4);
            $iPropParentID->setValue($iChild1, 3);
            $iPropName->setValue($iChild1, 'Lisa');
            $iPropIChildren->setValue($iChild1, []);

            $iChild2 = new Person();
            $iPropPersonID->setValue($iChild2, 5);
            $iPropParentID->setValue($iChild2, 3);
            $iPropName->setValue($iChild2, 'Sage');
            $iPropIChildren->setValue($iChild2, []);

            $iPropIChildren->setValue($iPerson, [$iChild1, $iChild2]);

            $case =
            [
                Person::query()->findByID(3)->withGraphJoined('iChildren'),
                [
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iChildren:personID"=>4,
	                    "iChildren:name"=>"Lisa",
	                    "iChildren:parentID"=>3
                    ],
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iChildren:personID"=>5,
	                    "iChildren:name"=>"Sage",
	                    "iChildren:parentID"=>3
                    ]
                ],
                [
                    $iPerson
                ]
            ];

            return $case;
        };

        $cases['With graph joined one level deep, find by ID'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren, $iPropIParents)
        {
            $iPerson = new Person();
            $iPropPersonID->setValue($iPerson, 1);
            $iPropParentID->setValue($iPerson, null);
            $iPropName->setValue($iPerson, 'Test');

            $iChild1 = new Person();
            $iPropPersonID->setValue($iChild1, 2);
            $iPropParentID->setValue($iChild1, 1);
            $iPropName->setValue($iChild1, 'Child');
            $iPropIChildren->setValue($iChild1, []);

            $iChild2 = new Person();
            $iPropPersonID->setValue($iChild2, 1);
            $iPropParentID->setValue($iChild2, null);
            $iPropName->setValue($iChild2, 'Test');
            $iPropIChildren->setValue($iChild2, []);

            $iParent = clone $iPerson;

            $iPropIParents->setValue($iChild1, [$iParent]);
            $iPropIChildren->setValue($iPerson, [$iChild1]);

            $case =
            [
                Person::query()->withGraphJoined('iChildren.iParents')->findByID(1),
                [
                    [
			            "personID"=>1,
			            "name"=>"Test",
			            "parentID"=>null,
			            "iChildren:personID"=>2,
			            "iChildren:name"=>"Child",
			            "iChildren:parentID"=>1,
			            "iChildren:iParents:personID"=>1,
			            "iChildren:iParents:name"=>"Test",
			            "iChildren:iParents:parentID"=>null
                    ]
                ],
                [
                    $iPerson
                ]
            ];

            return $case;
        };

        $cases['With graph joined through'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren)
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 1;
            $iPerson->name = 'Test';
            $iPerson->parentID = null;

            $iSchool = ModelTester::create(School::class);
            $iSchool->schoolID = 1;
            $iSchool->name = 'Test School';

            $iPerson->iSchools = [$iSchool];

            $case =
            [
                Person::query()
                    ->withGraphJoined('iSchools')
                    ->findByID(3),
                [

                ],
                [
                    $iPerson
                ]
            ];
            
            return $case;
        };

        /*
        $cases['With graph joined one level deep'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren)
        {
            $case =
            [
                // Person::query()->withGraphJoined('iParents.iChildren')->findByID(1),
                Person::query(),
                [
		            [
			            "personID"=>1,
			            "name"=>"Test",
			            "parentID"=>null,
			            "iChildren:personID"=>2,
			            "iChildren:name"=>"Child",
			            "iChildren:parentID"=>1,
			            "iChildren:iParents:personID"=>1,
			            "iChildren:iParents:name"=>"Test",
			            "iChildren:iParents:parentID"=>null
		            ],
		            [
			            "personID"=>3,
			            "name"=>"Magnus",
			            "parentID"=>null,
			            "iChildren:personID"=>5,
			            "iChildren:name"=>"Sage",
			            "iChildren:parentID"=>3,
			            "iChildren:iParents:personID"=>3,
			            "iChildren:iParents:name"=>"Magnus",
			            "iChildren:iParents:parentID"=>null
		            ],
		            [
			            "personID"=>3,
			            "name"=>"Magnus",
			            "parentID"=>null,
			            "iChildren:personID"=>4,
			            "iChildren:name"=>"Lisa",
			            "iChildren:parentID"=>3,
			            "iChildren:iParents:personID"=>3,
			            "iChildren:iParents:name"=>"Magnus",
			            "iChildren:iParents:parentID"=>null
		            ],
                ],
                [
                    [
                        'personID'=>1,
                        'name'=>'Test',
                        'parentID'=>null,
                        'iChildren'=>
                        [
                            [
                                'personID'=>2,
                                'name'=>'Child',
                                'parentID'=>1,
                                'iParents'=>
                                [
                                    [
                                        'personID'=>1,
                                        'name'=>'Test',
                                        'parentID'=>null,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'personID'=>3,
                        'name'=>'Magnus',
                        'parentID'=>null,
                        'iChildren'=>
                        [
                            [
                                'personID'=>5,
                                'name'=>'Sage',
                                'parentID'=>3,
                                'iParents'=>
                                [
                                    [
                                        'personID'=>3,
                                        'name'=>'Magnus',
                                        'parentID'=>null,
                                    ]
                                ]
                            ],
                            [
                                'personID'=>4,
                                'name'=>'Lisa',
                                'parentID'=>3,
                                'iParents'=>
                                [
                                    [
                                        'personID'=>3,
                                        'name'=>'Magnus',
                                        'parentID'=>null,
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ];

            return $case;
        };
*/
        /*
        $cases['With graph joined one level deep, find by ID'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren, $iPropIParents)
        {
            $iPerson = new Person();
            $iPropPersonID->setValue($iPerson, 1);
            $iPropParentID->setValue($iPerson, null);
            $iPropName->setValue($iPerson, 'Test');
            // $iPropIChildren->setValue($iPerson, []);

            $iChild1 = new Person();
            $iPropPersonID->setValue($iChild1, 2);
            $iPropParentID->setValue($iChild1, 1);
            $iPropName->setValue($iChild1, 'Child');
            $iPropIChildren->setValue($iChild1, []);

            $iChild2 = new Person();
            $iPropPersonID->setValue($iChild2, 1);
            $iPropParentID->setValue($iChild2, null);
            $iPropName->setValue($iChild2, 'Test');
            $iPropIChildren->setValue($iChild2, []);

            $iPropIChildren->setValue($iPerson, [$iChild1]);
            $iPropIParents->setValue($iChild1, [$iPerson]);
            
            $case =
            [
                // Person::query()->withGraphJoined('iChildren.[iParents, iChildren]')->findByID(1),
                Person::query()->withGraphJoined(['iChildren'=>['iParents', 'iChildren']])->findByID(1),
                // Person::query(),
                [
                    [
			            "personID"=>1,
			            "name"=>"Test",
			            "parentID"=>null,

			            "iChildren:personID"=>2,
			            "iChildren:name"=>"Child",
			            "iChildren:parentID"=>1,

			            "iChildren:iParents:personID"=>1,
			            "iChildren:iParents:name"=>"Test",
			            "iChildren:iParents:parentID"=>null,

			            "iChildren:iChildren:personID"=>1,
			            "iChildren:iChildren:name"=>"Test",
			            "iChildren:iChildren:parentID"=>null
                    ]
                ],
                [
                    $iPerson
                ]
            ];

            return $case;
        };

        */

        foreach($cases as $name=>$caseFn)
        {
            $cases[$name] = $caseFn();
        }

        return $cases;
    }

	/**
	 * @param array<string, array<string, string|Model>> $iExpected
	 * @dataProvider caseProvider
	 */
    public function testQueryBuilder(ModelQueryBuilder $iQueryBuilder, array $queryResults, array $iExpected): void
    {
        $iQueryBuilder->preCompile();

        $iReflectionClass = new \ReflectionClass($iQueryBuilder);
        $iMethodCreateGraphFromResults = $iReflectionClass->getMethod('createGraphFromResults');
        $iMethodCreateGraphFromResults->setAccessible(true);

        $iMethodCreateModelsFromResultsGraph = $iReflectionClass->getMethod('createModelsFromResultsGraph');
        $iMethodCreateModelsFromResultsGraph->setAccessible(true);

        $resultsGraph = $iMethodCreateGraphFromResults->invoke($iQueryBuilder, $queryResults);

        $iModels = $iMethodCreateModelsFromResultsGraph->invoke($iQueryBuilder, $resultsGraph);

        $this->assertEquals($iExpected, $iModels);
    }
}
