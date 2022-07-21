<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler\DbTableGateway;

use Laminas\Db\Adapter\Adapter;
use Laminas\Session\SaveHandler\DbTableGateway;
use LaminasTest\Session\SaveHandler\AbstractDbTableGatewayTest;

use function extension_loaded;
use function getenv;
use function sprintf;

class MysqliAdapterTest extends AbstractDbTableGatewayTest
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
                    '%s tests with Mysqli adapter are not enabled due to missing Mysqli extension',
                    DbTableGateway::class
                )
            );
        }

        return new Adapter([
            'driver'   => 'mysqli',
            'host'     => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_HOSTNAME'),
            'user'     => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_PASSWORD'),
            'dbname'   => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MYSQL_DATABASE'),
        ]);
    }
}
