<?php


namespace Tests\Integration;

use Codeception\Exception\InjectionException;
use Exception;
use Tests\Support\TIntegration;
use Tests\Support\IntegrationTester;
use Tests\Support\ObjectTester;
use Tests\Support\Person;

class ExampleCest
{
    use TIntegration;

    protected IntegrationTester $tester;

    /** @return void  */
    protected function _before(IntegrationTester $I): void
    {
        $this->initalizeQarium($I);
    }

    /**
     * @param IntegrationTester $I
     * @return void
     * @throws InjectionException
     */
    public function createUserTest(IntegrationTester $I): void
    {
        return;

        $I->haveInDatabase('users', [
            'id'    => 1,
            'name'  => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);

        $I->seeInDatabase('users', ['name' => 'John Doe']);
    }

    /**
     * @param IntegrationTester $I
     * @return void
     * @throws InjectionException
     */
    public function retrieveUserTest(IntegrationTester $I): void
    {
        return;

        $I->haveInDatabase('users', [
            'id'    => 2,
            'name'  => 'Jane Doe',
            'email' => 'jane.doe@example.com'
        ]);

        $I->seeInDatabase('users', ['id' => 2]);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testSomeFeature(IntegrationTester $I): void
    {
        $this->initalizeQarium($I);

        $I->haveInDatabase('Persons', [
            'personID' => 1,
            'parentID' => null,
            'name'     => 'John Doe',
        ]);

        $iPersonQuery = Person::query()
            ->findById(1);

        $iPerson = $iPersonQuery->run();

        $TestPerson = ObjectTester::create($iPerson);

        $I->assertEquals(1, $TestPerson->personID);
        $I->assertEquals(null, $TestPerson->parentID);
        $I->assertEquals('John Doe', $TestPerson->name);
    }
}
