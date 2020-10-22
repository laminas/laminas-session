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

class PdoMysqlAdapterTest extends DbTableGatewayTest
{
    /**
     * @return Adapter
     */
    protected function getAdapter()
    {
        if (! getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL')) {
            self::markTestSkipped(
                sprintf(
                    '%s tests with MySQL are disabled',
                    DbTableGateway::class
                )
            );
        }

        if (! extension_loaded('mysqli')) {
            self::markTestSkipped(
                sprintf(
                    '%s tests with PDO_Mysql adapter are not enabled due to missing PDO_Mysql extension',
                    DbTableGateway::class
                )
            );
        }

        return new Adapter([
            'driver' => 'pdo_mysql',
            'host' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_HOSTNAME'),
            'user' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_PASSWORD'),
            'dbname' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_DATABASE'),
        ]);
    }
}
