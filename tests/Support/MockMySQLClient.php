<?php

declare(strict_types=1);

namespace Tests\Support;

use Sharksmedia\SharQ\Client\MySQL;
use Sharksmedia\SharQ\Config;
use Sharksmedia\SharQ\Query;

class MockMySQLClient extends MySQL
{
    private \Closure $executor;

    public function __construct(Config $iConfig, \Closure $executor)
    {// 2023-05-08
        $this->iConfig  = $iConfig;
        $this->executor = $executor;
    }

    /*
     * 2023-05-08
     * @throws \PDOException if connection fails
     */
    public function initializeDriver(): void
    {// 2023-05-08
        $iConfig = $this->iConfig;

        $this->isInitialized = true;
    }

    /**
     * 2023-06-12
     * @param Query $iQuery
     * @param array<int, int> $options
     * @return \PDOStatement
     * @throws \PDOException
     */
    public function query(Query $iQuery, array $options = []): \PDOStatement
    {
        $executor = $this->executor;

        return $executor($iQuery, $options);
    }
}
