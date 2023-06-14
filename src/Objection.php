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
    private static array $iClients = [];

    public static function setClient(Client $iClient): void
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.
        self::$iClients['default'] = $iClient;
    }

    public static function getClient(?string $clientID=null): Client
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.

        $clientID = $clientID ?? self::getDefaultClientID();

        if(!isset(self::$iClients[$clientID])) throw new \Exception('Database with ID "'.$clientID.'" does not exis');

        return self::$iClients[$clientID];
    }

    public static function getDefaultClientID(): string
    {// 2023-06-12
        return 'default';
    }

}
