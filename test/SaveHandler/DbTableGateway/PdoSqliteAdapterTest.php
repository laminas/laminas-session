<?php

namespace LaminasTest\Session\SaveHandler\DbTableGateway;

use Laminas\Db\Adapter\Adapter;
use Laminas\Session\SaveHandler\DbTableGateway;
use LaminasTest\Session\SaveHandler\DbTableGatewayTest;

class PdoSqliteAdapterTest extends DbTableGatewayTest
{
    /**
     * @return Adapter
     */
    protected function getAdapter()
    {
        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped(
                sprintf(
                    '%s tests with PDO_Sqlite adapter are not enabled due to missing PDO_Sqlite extension',
                    DbTableGateway::class
                )
            );
        }

        return new Adapter([
            'driver' => 'pdo_sqlite',
            'database' => ':memory:',
        ]);
    }
}
