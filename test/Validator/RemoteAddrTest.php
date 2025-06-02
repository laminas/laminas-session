<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\RemoteAddr;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Validator\RemoteAddr
 */
class RemoteAddrTest extends TestCase
{
    protected array $backup;

    protected RemoteAddr $defaultRemoteAddr;

    protected function setUp(): void
    {
        $this->defaultRemoteAddr = new RemoteAddr(initial: '0.1.2.3');
    }

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

    public function testGetData(): void
    {
        $validator = new RemoteAddr(initial: '0.1.2.3');
        self::assertEquals('0.1.2.3', $validator->data);
    }

    public function testDefaultUseProxy(): void
    {
        self::assertFalse($this->defaultRemoteAddr->getUseProxy());
    }

    public function testRemoteAddrWithoutProxy(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr(initial: $_SERVER['REMOTE_ADDR']);
        self::assertEquals('0.1.2.3', $validator->data);
        $this->restore();
    }

    public function testIsValid(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        $_SERVER['REMOTE_ADDR'] = '1.1.2.3';
        self::assertFalse($validator->isValid());
        $this->restore();
    }

    public function testIgnoreProxyByDefault(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']    = '0.1.2.3';
        $_SERVER['HTTP_CLIENT_IP'] = '1.1.2.3';
        $validator                 = new RemoteAddr(initial: $_SERVER['REMOTE_ADDR']);
        self::assertEquals('0.1.2.3', $validator->data);
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

        $validator = new RemoteAddr(options: $options);
        self::assertEquals('1.1.2.3', $validator->data);
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

        $validator = new RemoteAddr(options: $options);
        self::assertEquals('2.1.2.3', $validator->data);
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

        $validator = new RemoteAddr(options: $options);
        self::assertEquals('1.1.2.3', $validator->data);
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

        $validator = new RemoteAddr($_SERVER['REMOTE_ADDR'], $options);
        self::assertEquals('0.1.2.3', $validator->data);
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

        $validator = new RemoteAddr(options: $options);
        self::assertEquals('2.1.2.3', $validator->data);
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

        $validator = new RemoteAddr($_SERVER['REMOTE_ADDR'], $options);
        self::assertEquals('0.1.2.3', $validator->data);
        $this->restore();
    }

    public function testGetName(): void
    {
        self::assertEquals(RemoteAddr::class, $this->defaultRemoteAddr->getName());
    }

    public function testUnknownServerHeader(): void
    {
        $this->backup();

        $options = [
            'use_proxy'    => true,
            'proxy_header' => 'Unknown-Header',
        ];

        $validator = new RemoteAddr(options: $options);
        self::assertEmpty($validator->data);
        $this->restore();
    }
}
