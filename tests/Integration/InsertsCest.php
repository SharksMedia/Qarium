<?php


namespace Tests\Integration;

use Sharksmedia\Qarium\Qarium;
use Tests\Support\ObjectTester;
use Tests\Support\TIntegration;
use Tests\Support\IntegrationTester;
use Tests\Support\Person;

class InsertsCest
{
    use TIntegration;

    /** @return void  */
    public function _before(IntegrationTester $I): void
    {
        $this->initalizeQarium($I);

        $iClient = Qarium::getClient();

        $iClient->beginTransaction();
    }

    public function _after(IntegrationTester $I): void
    {
        $iClient = Qarium::getClient();

        $iClient->rollback();
    }

    public function testInsertPerson(IntegrationTester $I): void
    {
        $personData = [
            'personID' => 1,
            'parentID' => null,
            'name'     => 'John Doe',
        ];

        Person::query()
            ->insert($personData)
            ->run();

        $I->seeInDatabase('Persons', $personData);
    }

    public function testInsertMultiplePersons(IntegrationTester $I): void
    {
        $I->expectThrowable(new \Exception('Batch insert only works with Postgresql and SQL Server'), function() use ($I)
        {
            $personsData = [
                [
                    'personID' => 1,
                    'parentID' => null,
                    'name'     => 'John Doe',
                ],
                [
                    'personID' => 2,
                    'parentID' => null,
                    'name'     => 'Jane Doe',
                ],
            ];

            Person::query()
                ->insert($personsData)
                ->run();

            $I->seeInDatabase('Persons', $personsData[0]);
            $I->seeInDatabase('Persons', $personsData[1]);
        });
    }

    public function testInsertAndFetchFirstAutoIncrement(IntegrationTester $I): void
    {
        $personData = [
            'parentID' => null,
            'name'     => 'John Doe',
        ];

        $iPerson = Person::query()
            ->insertAndFetch($personData)
            ->first()
            ->run();

        $I->seeInDatabase('Persons', [
            'personID' => $iPerson->getPersonID(),
            'parentID' => null,
            'name'     => 'John Doe',
        ]);

        $I->assertInstanceOf(Person::class, $iPerson);

        $TestPerson = ObjectTester::create($iPerson);

        // $I->assertEquals(1, $TestPerson->personID);
        $I->assertEquals(null, $TestPerson->parentID);
        $I->assertEquals('John Doe', $TestPerson->name);
    }

    public function testInsertAndFetchFirst(IntegrationTester $I): void
    {
        $personData = [
            'personID' => 1,
            'parentID' => null,
            'name'     => 'John Doe',
        ];

        $iPerson = Person::query()
            ->insertAndFetch($personData)
            ->first()
            ->run();

        $I->seeInDatabase('Persons', $personData);
        $I->assertInstanceOf(Person::class, $iPerson);

        $TestPerson = ObjectTester::create($iPerson);

        $I->assertEquals(1, $TestPerson->personID);
        $I->assertEquals(null, $TestPerson->parentID);
        $I->assertEquals('John Doe', $TestPerson->name);
    }

    public function testUpsertAndFetchFirst(IntegrationTester $I): void
    {
        $personData = [
            'personID' => 1,
            'parentID' => null,
            'name'     => 'John Doe',
        ];

        Person::query()
            ->insert($personData)
            ->run();

        $I->seeInDatabase('Persons', $personData);

        $personData['name'] = 'Jane Doe';

        $iPerson = Person::query()
            ->insertAndFetch($personData)
            ->first()
            ->onConflict('personID')
            ->merge()
            ->run();

        $I->seeInDatabase('Persons', $personData);
        $I->assertInstanceOf(Person::class, $iPerson);

        $TestPerson = ObjectTester::create($iPerson);

        $I->assertEquals(1, $TestPerson->personID);
        $I->assertEquals(null, $TestPerson->parentID);
        $I->assertEquals('Jane Doe', $TestPerson->name);
    }

    public function testInsertMultipleAndFetch(IntegrationTester $I): void
    {
        $I->expectThrowable(new \Exception('Batch insert only works with Postgresql and SQL Server'), function() use ($I)
        {
            $personData =
            [
                [
                    'personID' => 1,
                    'parentID' => null,
                    'name'     => 'John Doe',
                ],
                [
                    'personID' => 2,
                    'parentID' => null,
                    'name'     => 'Jane Doe',
                ]
            ];

            $iPersons = Person::query()
                ->insertAndFetch($personData)
                ->run();

            $I->seeInDatabase('Persons', $personData);
            $I->assertCount(2, $iPersons);
            $I->assertInstanceOf(Person::class, $iPersons[0]);

            $TestPerson1 = ObjectTester::create($iPersons[0]);
            $TestPerson2 = ObjectTester::create($iPersons[1]);

            $I->assertEquals(1, $TestPerson1->personID);
            $I->assertEquals(null, $TestPerson1->parentID);
            $I->assertEquals('John Doe', $TestPerson1->name);

            $I->assertEquals(2, $TestPerson2->personID);
            $I->assertEquals(null, $TestPerson2->parentID);
            $I->assertEquals('Jane Doe', $TestPerson2->name);
        });
    }

    public function testUpsertPerson(IntegrationTester $I): void
    {
        $personData = [
            'personID' => 1,
            'parentID' => null,
            'name'     => 'John Doe',
        ];

        Person::query()
            ->insert($personData)
            ->onConflict('personID')
            ->merge($personData)
            ->run();

        $I->seeInDatabase('Persons', $personData);

        $personData['name'] = 'Jane Doe';

        Person::query()
            ->insert($personData)
            ->onConflict('personID')
            ->merge($personData)
            ->run();

        $I->seeInDatabase('Persons', $personData);
    }
}
