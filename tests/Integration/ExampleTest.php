<?php


namespace Tests\Integration;

use Sharksmedia\Objection\Objection;
use Sharksmedia\QueryBuilder\QueryCompiler;
use Sharksmedia\QueryBuilder\Client;
use Sharksmedia\QueryBuilder\Config;
use Tests\Support\IntegrationTester;
use Tests\Support\Person;

class ExampleTest extends \Codeception\Test\Unit
{

    protected IntegrationTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testSomeFeature()
    {
        $iConfig = (new Config(Config::CLIENT_MYSQL))
            // ->host('172.21.74.3')
            ->host('127.0.0.1')
            // ->port('3306')
            ->port('5060')
            ->user('bluemedico_admin')
            ->password('926689c103aeb7b7')
            ->database('ObjectionTest')
            ->charset('utf8mb4');

        $iClient = Client::create($iConfig); // MySQL client
        $iClient->initializeDriver();

        Objection::setClient($iClient);


        $iPersonQuery = Person::query()
                        ->findByID(1);

        $iQueryCompiler = new QueryCompiler($iClient, $iPersonQuery, []);

        $iQuery = $iQueryCompiler->toSQL();

        $iPerson = $iPersonQuery->run();
    }
}
