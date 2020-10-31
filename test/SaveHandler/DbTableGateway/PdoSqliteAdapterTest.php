<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

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
