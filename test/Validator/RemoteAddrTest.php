<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\RemoteAddr;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * @covers \Laminas\Session\Validator\RemoteAddr
 */
class RemoteAddrTest extends TestCase
{
    protected array $backup;

    protected RemoteAddr $defaultRemoteAddr;
    private ReflectionObject $remoteAddrReflection;

    protected function setUp(): void
    {
        $this->defaultRemoteAddr    = new RemoteAddr();
        $this->remoteAddrReflection = new ReflectionObject($this->defaultRemoteAddr);
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
        $validator = new RemoteAddr('0.1.2.3');
        self::assertEquals('0.1.2.3', $validator->getData());
    }

    public function testDefaultUseProxy(): void
    {
        self::assertFalse($this->defaultRemoteAddr->getUseProxy());
    }

    public function testRemoteAddrWithoutProxy(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        self::assertEquals('0.1.2.3', $validator->getData());
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
        $validator                 = new RemoteAddr();
        self::assertEquals('0.1.2.3', $validator->getData());
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
        $validator                       = new RemoteAddr(null, $options);
        self::assertEquals('1.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEquals('2.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEquals('1.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEquals('0.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEquals('2.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEquals('0.1.2.3', $validator->getData());
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

        $validator = new RemoteAddr(null, $options);
        self::assertEmpty($validator->getData());
        $this->restore();
    }

    public function testGetIpAddressFromProxy(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '192.168.0.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8, 10.0.0.1';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => [
                '192.168.0.10',
                '10.0.0.1',
            ],
        ];

        $reflectionMethod   = $this->remoteAddrReflection->getMethod('getIpAddress');
        $remoteAddr         = new RemoteAddr(null, $options);
        $getClientIpAddress = (string) $reflectionMethod->invoke($remoteAddr);

        $this->assertEquals('8.8.8.8', $getClientIpAddress);
    }

    public function testGetIpAddressFromProxyRemoteAddressNotTrusted(): void
    {
        $_SERVER['REMOTE_ADDR']          = '1.1.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8, 10.0.0.1';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => [
                '10.0.0.1',
            ],
        ];

        $reflectionMethod   = $this->remoteAddrReflection->getMethod('getIpAddress');
        $remoteAddr         = new RemoteAddr(null, $options);
        $getClientIpAddress = (string) $reflectionMethod->invoke($remoteAddr);

        $this->assertEquals('1.1.1.1', $getClientIpAddress);
    }

    /**
     * Test to prevent attack on the HTTP_X_FORWARDED_FOR header
     * The client IP is always the first on the left
     *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     */
    public function testGetIpAddressFromProxyFakeData(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.0.10';
        // 1.1.1.1 is the first IP address from the right not representing a known proxy server; as such, we
        // must treat it as a client IP.
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8, 10.0.0.2, 1.1.1.1, 10.0.0.1';

        $options = [
            'use_proxy'       => true,
            'trusted_proxies' => [
                '192.168.0.10',
                '10.0.0.1',
                '10.0.0.2',
            ],
        ];

        $reflectionMethod   = $this->remoteAddrReflection->getMethod('getIpAddress');
        $remoteAddr         = new RemoteAddr(null, $options);
        $getClientIpAddress = (string) $reflectionMethod->invoke($remoteAddr);

        $this->assertEquals('1.1.1.1', $getClientIpAddress);
    }
}
