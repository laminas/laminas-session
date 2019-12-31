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

class PgsqlAdapterTest extends DbTableGatewayTest
{
    /**
     * @return Adapter
     */
    protected function getAdapter()
    {
        if (! getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL')) {
            $this->markTestSkipped(sprintf(
                '%s tests with Pgsql adapter are disabled',
                DbTableGateway::class
            ));
        }

        if (! extension_loaded('mysqli')) {
            $this->markTestSkipped(sprintf(
                '%s tests with Pgsql adapter are not enabled due to missing Pgsql extension',
                DbTableGateway::class
            ));
        }

        return new Adapter([
            'driver' => 'pgsql',
            'host' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_HOSTNAME'),
            'user' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_PASSWORD'),
            'dbname' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_DATABASE'),
        ]);
    }
}
