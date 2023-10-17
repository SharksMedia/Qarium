<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class DbConfig extends \Codeception\Module
{
    public function getPDO()
    {
        /** @var \Codeception\Module\Db $db */
        $db  = $this->getModule('Db');

        return $db->_getDbh();
    }

    public function getDbDsn()
    {
        $config = $this->getModule('Db')->_getConfig();

        return $config['dsn'];
    }

    public function getDbHost()
    {
        $dsn = $this->getDbDsn();

        return $this->parseDsn($dsn, 'host');
    }

    public function getDbPort()
    {
        $dsn = $this->getDbDsn();

        return $this->parseDsn($dsn, 'port');
    }

    public function getDbName()
    {
        $dsn = $this->getDbDsn();

        return $this->parseDsn($dsn, 'dbname');
    }

    public function getDbUser()
    {
        $config = $this->getModule('Db')->_getConfig();

        return $config['user'];
    }

    public function getDbPassword()
    {
        $config = $this->getModule('Db')->_getConfig();

        return $config['password'];
    }

    private function parseDsn($dsn, $key)
    {
        $dsn       = preg_replace('/^.*:/', '', $dsn);
        $dsnValues = explode(';', $dsn);

        foreach ($dsnValues as $value)
        {
            list($dsnKey, $dsnValue) = explode('=', $value);

            if ($dsnKey === $key)
            {
                return $dsnValue;
            }
        }

        return null;
    }
}
