<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler\DbTableGateway;

use Laminas\Db\Adapter\Adapter;
use Laminas\Session\SaveHandler\DbTableGateway;
use LaminasTest\Session\SaveHandler\AbstractDbTableGatewayTest;

use function extension_loaded;
use function getenv;
use function sprintf;

class PgsqlAdapterTest extends AbstractDbTableGatewayTest
{
    protected function getAdapter(): Adapter
    {
        $enabled = (bool) getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL');
        if (! $enabled) {
            self::markTestSkipped(
                sprintf(
                    '%s tests with Pgsql adapter are disabled',
                    DbTableGateway::class
                )
            );
        }

        if (! extension_loaded('pdo_pgsql')) {
            self::markTestSkipped(
                sprintf(
                    '%s tests with Pgsql adapter are not enabled due to missing Pgsql extension',
                    DbTableGateway::class
                )
            );
        }

        return new Adapter([
            'driver'   => 'pgsql',
            'host'     => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_HOSTNAME'),
            'user'     => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_PASSWORD'),
            'dbname'   => getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_PGSQL_DATABASE'),
        ]);
    }
}
