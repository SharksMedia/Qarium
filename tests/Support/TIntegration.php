<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\Qarium\Qarium;
use Sharksmedia\SharQ\Client;
use Sharksmedia\SharQ\Config;
use Sharksmedia\SharQ\CustomPDO;
use Tests\Support\IntegrationTester;

trait TIntegration
{
    protected $iClient;
    public function getSharQClient(IntegrationTester $I): Client
    {
        // if ($this->iClient)
        // {
        //     return $this->iClient;
        // }

        // $host     = $I->getDbHost();
        // $port     = $I->getDbPort();
        // $database = $I->getDbName();
        // $user     = $I->getDbUser();
        // $password = $I->getDbPassword();

        $iConfig = (new Config(Config::CLIENT_MYSQL))
            // ->host($host)
            // ->port($port ?? 3306)
            // ->user($user)
            // ->password($password)
            ->database('testdb')
            ->charset('utf8mb4');

        $iClient = Client::create($iConfig); // MySQL client

        // Get the PDO instance
        $pdo = $I->getPDO();

        $customPDO = CustomPDO::createFromPDO($pdo);

        $reflection = new \ReflectionClass($iClient);
        $property   = $reflection->getProperty('driver');
        $property->setAccessible(true);  // Make the property accessible
        $property->setValue($iClient, $customPDO);  // Set the property's value

        $property   = $reflection->getProperty('isInitialized');
        $property->setAccessible(true);  // Make the property accessible
        $property->setValue($iClient, true);  // Set the property's value

        $this->iClient = $iClient;

        return $iClient;
    }

    public function initalizeQarium(IntegrationTester $I): void
    {
        $iClient = $this->getSharQClient($I);

        // $iClient->initializeDriver();

        Qarium::setClient($iClient);
    }
}
