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

    public static function getClient(?string $databaseID=null): Client
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.

        $databaseID = $databaseID ?? self::getDefaultClientID();

        if(!isset(self::$iClients[$databaseID])) throw new \Exception('Database with ID "'.$databaseID.'" does not exis');

        return self::$iClients[$databaseID];
    }

    public static function getDefaultClientID(): string
    {// 2023-06-12
        return 'default';
    }

}
