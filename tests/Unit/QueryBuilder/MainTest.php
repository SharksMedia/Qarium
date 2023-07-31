<?php

declare(strict_types=1);

namespace Tests\Unit\QueryBuilder;

use Sharksmedia\Objection\ModelQueryBuilder;
use Tests\Support\Person;

class MainTest extends \Codeception\Test\Unit
{
    public function testModelClassShouldReturnTheModelClass()
    {
        $iBuilder = ModelQueryBuilder::forClass(Person::class);

        $this->assertEquals(Person::class, $iBuilder->getModelClass());
    }

    /**
     * Method is called before test file run
     */
    protected function _before(): void
    {// 2023-06-15
        // FIXME: Cleanup the database
    }

    protected function _after(): void
    {// 2023-06-15
        // FIXME: Cleanup the database
    }

    public function caseProvider(): array
    {
        // Providers are run before anything else, so we are initalizing the client here.
        Objection::setClient(self::getClient());

        $cases = [];

        $cases['modelClass() should return the model class'] = function()
        {
            
            $case =
            [
                Person::query()
                    ->insert(['name'=>'Magnus', 'parentID'=>null]),
                [
                    'mysql'=>
                    [
                        'sql'=>'INSERT INTO `Persons` (`name`, `parentID`) VALUES (?, NULL)',
                        'bindings'=>['Magnus'],
                        'expected'=>$iPerson->getObject(),
                    ]
                ]
            ];
            
            return $case;
        };

    }

}
