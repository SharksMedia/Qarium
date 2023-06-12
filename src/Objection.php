<?php

/**
 * 2023-06-12
 * @author Magnus Schmidt Rasmussen <magnus@sharksmedia.dk>
 */

declare(strict_types=1);

namespace Sharksmedia\Objection;

use Sharksmedia\Database\Database;

class Objection
{
    private static array $iDatabases = [];

    public static function setDatabase(Database $iDatabase): void
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.
        self::$iDatabases['default'] = $iDatabase;
    }

    public static function getClient(?string $databaseID=null): Database
    {// 2023-06-12
        // FIXME: Use an identifier from the database config instead of the object itself.

        $databaseID = $databaseID ?? self::getDefaultDatabaseID();

        if(!isset(self::$iDatabases[$databaseID])) throw new \Exception('Database with ID "'.$databaseID.'" does not exis');

        return self::$iDatabases[$databaseID];
    }

    public static function getDefaultDatabaseID(): string
    {// 2023-06-12
        return 'default';
    }

}
