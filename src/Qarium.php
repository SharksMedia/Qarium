<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\SharQ\Client;
use Sharksmedia\SharQ\SharQ;

class Qarium
{
    private const DEFAULT_CLIENT_ID = 'DEFAULT';

    private static array $iClients = [];

    public static function setClient(Client $iClient, ?string $clientID=null): void
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.
        static::$iClients[$clientID ?? static::getDefaultClientID()] = $iClient;
    }

    public static function getClient(?string $clientID=null): Client
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.

        $clientID = $clientID ?? static::getDefaultClientID();

        if(!isset(static::$iClients[$clientID])) throw new \Exception('Database with ID "'.$clientID.'" does not exist');

        return static::$iClients[$clientID];
    }

    public static function getDefaultClientID(): string
    {// 2023-06-12
        return self::DEFAULT_CLIENT_ID;
    }

    /**
     * 2023-06-12
     * @param SharQ $iSharQ
     * @param class-string<Model> $modelClass
     * @return ModelSharQ
     */
    public static function initalize(Client $iClient, array $modelClasses): void
    {// 2023-06-12
        foreach($modelClasses as $modelClass)
        {
            $modelClass::fetchTableMetadata($iClient);
        }
    }

}
