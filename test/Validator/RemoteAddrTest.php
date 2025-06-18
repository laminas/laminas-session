<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\RemoteAddr;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Validator\RemoteAddr
 */
class RemoteAddrTest extends TestCase
{
    protected array $backup = [];

    protected function backup(): void
    {
        $this->backup = $_SERVER;
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CLIENT_IP']
        );
    }

    protected function restore(): void
    {
        $_SERVER = $this->backup;
    }

    public function testRemoteAddrWithoutProxy(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              =
            new RemoteAddr(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        self::assertEquals('0.1.2.3', $validator->current->remoteAddr);
        $this->restore();
    }

    public function testIsValid(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              =
            new RemoteAddr(new Environment(remoteAddr:  '0.1.1.3'), Environment::fromGlobals($_SERVER));
        self::assertFalse($validator->isValid());
        $this->restore();
    }

    public function testIgnoreProxyByDefault(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']    = '0.1.2.3';
        $_SERVER['HTTP_CLIENT_IP'] = '1.1.2.3';
        $validator                 = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER)
        );
        self::assertEquals('0.1.2.3', $validator->current->remoteAddr);
        $this->restore();
    }

    public function testHttpXForwardedFor(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.2.3';
        $options                         = [
            'use_proxy'       => true,
            'trusted_proxies' => ['0.1.2.3'],
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEquals('1.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testHttpClientIp(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => ['0.1.2.3'],
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEquals('2.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testUsesRightMostAddressWhenMultipleHttpXForwardedForAddressesPresent(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => ['0.1.2.3'],
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );

        self::assertEquals('1.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testShouldNotUseClientIpHeaderToTestProxyCapabilitiesByDefault(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        $_SERVER['HTTP_CLIENT_IP']       = '0.1.2.4';

        $options = [
            'trusted_proxies' => ['0.1.2.3'],
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEquals('0.1.2.3', $validator->current->remoteAddr);
        $this->restore();
    }

    public function testWillOmitTrustedProxyIpsFromXForwardedForMatching(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '1.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => ['1.1.2.3'],
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEquals('2.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testCanSpecifyWhichHeaderToUseStatically(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        $_SERVER['HTTP_CLIENT_IP']       = '0.1.2.4';

        $options = [
            'use_proxy'    => true,
            'proxy_header' => 'Client-Ip',
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEquals('0.1.2.3', $validator->current->remoteAddr);
        $this->restore();
    }

    public function testGetName(): void
    {
        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER)
        );
        self::assertEquals(RemoteAddr::class, $validator->getName());
    }

    public function testUnknownServerHeader(): void
    {
        $this->backup();

        $options = [
            'use_proxy'    => true,
            'proxy_header' => 'Unknown-Header',
        ];

        $validator = new RemoteAddr(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER),
            $options
        );
        self::assertEmpty($validator->current->remoteAddr);
        $this->restore();
    }
}
