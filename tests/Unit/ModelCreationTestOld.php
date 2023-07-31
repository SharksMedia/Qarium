<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;
use Sharksmedia\Objection\Objection;
use Sharksmedia\Objection\ModelQueryBuilder;
use Sharksmedia\QueryBuilder\QueryBuilder;
use Sharksmedia\QueryBuilder\Config;
use Sharksmedia\QueryBuilder\Client;
use Tests\Support\Person;
use Tests\Support\School;
use Tests\Support\Student;
use Tests\Support\City;
use Tests\Support\Country;
use Tests\Support\ModelTester;

class ModelCreationTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected static function getClient(): Client
    {// 2023-06-15
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            ->host('127.0.0.1')
            ->port(3306)
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

        $cases['With graph joined belongs to one relation'] = function()
        {
            $iSchool = ModelTester::create(School::class);
            $iSchool->schoolID = 1;
            $iSchool->name = 'SharkCamp';
            $iSchool->cityID = 1;

            $iCity = ModelTester::create(City::class);
            $iCity->cityID = 1;
            $iCity->name = 'Vejle';
            $iCity->countryID = 57;

            $iSchool->iCity = $iCity->getObject();

            $case =
            [
                School::query()
                    ->withGraphJoined('iCity')
                    ->findByID(1),
                [
                    [
	                    "schoolID"=>1,
	                    "name"=>"SharkCamp",
	                    "cityID"=>1,
	                    "iCity:cityID"=>1,
	                    "iCity:countryID"=>57,
	                    "iCity:name"=>"Vejle"
                    ]
                ],
                $iSchool->getObject()
            ];
            
            return $case;
        };

        $cases['With graph joined has many relation'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 3;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $iChild1 = ModelTester::create(Person::class);
            $iChild1->personID = 4;
            $iChild1->name = 'Lisa';
            $iChild1->parentID = 3;

            $iChild2 = ModelTester::create(Person::class);
            $iChild2->personID = 5;
            $iChild2->name = 'Sage';
            $iChild2->parentID = 3;

            $iPerson->iChildren = [$iChild1->getObject(), $iChild2->getObject()];

            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren')
                    ->findByID(3),
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
                $iPerson->getObject()
            ];
            
            return $case;
        };

        $cases['With graph joined has one relation'] = function()
        {
            $iCity = ModelTester::create(City::class);
            $iCity->cityID = 1;
            $iCity->name = 'Vejle';
            $iCity->countryID = 57;

            $iCountry = ModelTester::create(Country::class);
            $iCountry->countryID = 57;
            $iCountry->name = 'Denmark';
            $iCountry->ISOCode2 = 'DK';
            $iCountry->ISOCode3 = 'DNK';

            $iCity->iCountry = $iCountry->getObject();

            $case =
            [
                City::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(1),
                [
                    [
	                    "cityID"=>1,
	                    "countryID"=>57,
	                    "name"=>"Vejle",
	                    "iCountry:countryID"=>57,
	                    "iCountry:name"=>"Denmark",
	                    "iCountry:ISOCode2"=>"DK",
	                    "iCountry:ISOCode3"=>"DNK"
                    ]
                ],
                $iCity->getObject()
            ];
            
            return $case;
        };

        $cases['With graph joined many to many relation'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 3;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $iSchool = ModelTester::create(School::class);
            $iSchool->schoolID = 1;
            $iSchool->name = 'SharkCamp';

            $iPerson->iSchools = [$iSchool->getObject()];

            $case =
            [
                Person::query()
                    ->withGraphJoined('iSchools')
                    ->findByID(3),
                [
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iSchools:schoolID"=>1,
	                    "iSchools:name"=>"SharkCamp"
                    ]
                ],
                $iPerson->getObject()
            ];
            
            return $case;
        };

        $cases['With graph joined has one relation'] = function()
        {
            $iCity = ModelTester::create(City::class);
            $iCity->cityID = 1;
            $iCity->name = 'Vejle';
            $iCity->countryID = 57;

            $iCountry = ModelTester::create(Country::class);
            $iCountry->countryID = 57;
            $iCountry->name = 'Denmark';
            $iCountry->ISOCode2 = 'DK';
            $iCountry->ISOCode3 = 'DNK';

            $iCity->iCountry = $iCountry->getObject();

            $case =
            [
                City::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(1),
                [
                    [
	                    "cityID"=>1,
	                    "countryID"=>57,
	                    "name"=>"Vejle",
	                    "iCountry:countryID"=>57,
	                    "iCountry:name"=>"Denmark",
	                    "iCountry:ISOCode2"=>"DK",
	                    "iCountry:ISOCode3"=>"DNK"
                    ]
                ],
                $iCity->getObject()
            ];
            
            return $case;
        };

        $cases['With graph joined has one through relation'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 3;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $iCountry = ModelTester::create(Country::class);
            $iCountry->countryID = 57;
            $iCountry->name = 'Denmark';
            $iCountry->ISOCode2 = 'DK';
            $iCountry->ISOCode3 = 'DNK';

            $iPerson->iCountry = $iCountry->getObject();

            $case =
            [
                Person::query()
                    ->withGraphJoined('iCountry')
                    ->findByID(3),
                [
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iCountry:countryID"=>57,
	                    "iCountry:name"=>"Denmark",
	                    "iCountry:ISOCode2"=>"DK",
	                    "iCountry:ISOCode3"=>"DNK"
                    ]
                ],
                $iPerson->getObject()
            ];
            
            return $case;
        };

        $cases['Basic with graph joined, find by id, no relations available'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 3;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $iCild1 = ModelTester::create(Person::class);
            $iCild1->personID = 4;
            $iCild1->name = 'Lisa';
            $iCild1->parentID = 3;

            $iCild2 = ModelTester::create(Person::class);
            $iCild2->personID = 5;
            $iCild2->name = 'Sage';
            $iCild2->parentID = 3;

            $iPerson->iChildren = [$iCild1->getObject(), $iCild2->getObject()];

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
                $iPerson->getObject()
            ];

            return $case;
        };

        $cases['With graph joined one level deep, find by ID'] = function() use($iPropPersonID, $iPropParentID, $iPropName, $iPropIChildren, $iPropIParents)
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 1;
            $iPerson->name = 'Test';
            $iPerson->parentID = null;

            $iParent = clone $iPerson;

            $iCild1 = ModelTester::create(Person::class);
            $iCild1->personID = 2;
            $iCild1->name = 'Child';
            $iCild1->parentID = 1;
            $iCild1->iParents = [$iParent->getObject()];

            $iPerson->iChildren = [$iCild1->getObject()];

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
                $iPerson->getObject()
            ];

            return $case;
        };

        $cases['With graph join multiple same level'] = function()
        {
            $iPerson = ModelTester::create(Person::class);
            $iPerson->personID = 3;
            $iPerson->name = 'Magnus';
            $iPerson->parentID = null;

            $iChild1 = ModelTester::create(Person::class);
            $iChild1->personID = 4;
            $iChild1->name = 'Lisa';
            $iChild1->parentID = 3;

            $iChild2 = ModelTester::create(Person::class);
            $iChild2->personID = 5;
            $iChild2->name = 'Sage';
            $iChild2->parentID = 3;

            $iSchool = ModelTester::create(School::class);
            $iSchool->schoolID = 1;
            $iSchool->name = 'SharkCamp';

            $iChild1->iSchools = [$iSchool->getObject()];
            $iChild1->iParents = [$iPerson->getObject()];

            $iChild2->iSchools = [$iSchool->getObject()];
            $iChild2->iParents = [$iPerson->getObject()];

            $iPerson->iChildren = [$iChild1->getObject(), $iChild2->getObject()];

            $case =
            [
                Person::query()
                    ->withGraphJoined('iChildren.[iParents, iSchools]')
                    ->findByID(3),
                [
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iChildren:personID"=>4,
	                    "iChildren:name"=>"Lisa",
	                    "iChildren:parentID"=>3,
	                    "iChildren:iSchools:schoolID"=>1,
	                    "iChildren:iSchools:name"=>"SharkCamp",
	                    "iChildren:iParents:personID"=>3,
	                    "iChildren:iParents:name"=>"Magnus",
	                    "iChildren:iParents:parentID"=>null
                    ],
                    [
	                    "personID"=>3,
	                    "name"=>"Magnus",
	                    "parentID"=>null,
	                    "iChildren:personID"=>5,
	                    "iChildren:name"=>"Sage",
	                    "iChildren:parentID"=>3,
	                    "iChildren:iSchools:schoolID"=>1,
	                    "iChildren:iSchools:name"=>"SharkCamp",
	                    "iChildren:iParents:personID"=>3,
	                    "iChildren:iParents:name"=>"Magnus",
	                    "iChildren:iParents:parentID"=>null
                    ]
                ],
                $iPerson->getObject()
            ];
            
            return $case;
        };

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
    public function testQueryBuilder(ModelQueryBuilder $iQueryBuilder, array $queryResults, $iExpected): void
    {
        $iQueryBuilder->preCompile();

        $iReflectionClass = new \ReflectionClass($iQueryBuilder);
        $iMethodCreateGraphFromResults = $iReflectionClass->getMethod('createGraphFromResults');
        $iMethodCreateGraphFromResults->setAccessible(true);

        $iMethodCreateModelsFromResultsGraph = $iReflectionClass->getMethod('createModelsFromResultsGraph');
        $iMethodCreateModelsFromResultsGraph->setAccessible(true);

        $resultsGraph = $iMethodCreateGraphFromResults->invoke($iQueryBuilder, $queryResults);

        $iModels = $iMethodCreateModelsFromResultsGraph->invoke($iQueryBuilder, $resultsGraph);

        if($iQueryBuilder->getMethod() === QueryBuilder::METHOD_FIRST) $iModels = $iModels[0] ?? null;

        $this->assertEquals($iExpected, $iModels);
    }
}
