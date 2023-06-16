<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\QueryBuilder\Client;

class Objection
{
    private const DEFAULT_CLIENT_ID = 'DEFAULT';

    private static array $iClients = [];

    public static function setClient(Client $iClient): void
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.
        self::$iClients[self::getDefaultClientID()] = $iClient;
    }

    public static function getClient(?string $clientID=null): Client
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.

        $clientID = $clientID ?? self::getDefaultClientID();

        if(!isset(self::$iClients[$clientID])) throw new \Exception('Database with ID "'.$clientID.'" does not exist');

        return self::$iClients[$clientID];
    }

    public static function getDefaultClientID(): string
    {// 2023-06-12
        return self::DEFAULT_CLIENT_ID;
    }

}
