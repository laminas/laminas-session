<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\RemoteAddr;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Validator\RemoteAddr
 */
class RemoteAddrTest extends TestCase
{
    protected $backup;

    protected function backup(): void
    {
        $this->backup = $_SERVER;
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CLIENT_IP']
        );
        RemoteAddr::setUseProxy(false);
        RemoteAddr::setTrustedProxies([]);
        RemoteAddr::setProxyHeader();
    }

    protected function restore(): void
    {
        $_SERVER = $this->backup;
        RemoteAddr::setUseProxy(false);
        RemoteAddr::setTrustedProxies([]);
        RemoteAddr::setProxyHeader();
    }

    public function testGetData(): void
    {
        $validator = new RemoteAddr('0.1.2.3');
        self::assertEquals('0.1.2.3', $validator->getData());
    }

    public function testDefaultUseProxy(): void
    {
        self::assertFalse(RemoteAddr::getUseProxy());
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
        RemoteAddr::setUseProxy(true);
        RemoteAddr::setTrustedProxies(['0.1.2.3']);
        $validator = new RemoteAddr();
        self::assertEquals('1.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testHttpClientIp(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3';
        RemoteAddr::setUseProxy(true);
        RemoteAddr::setTrustedProxies(['0.1.2.3']);
        $validator = new RemoteAddr();
        self::assertEquals('2.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testUsesRightMostAddressWhenMultipleHttpXForwardedForAddressesPresent(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        RemoteAddr::setUseProxy(true);
        RemoteAddr::setTrustedProxies(['0.1.2.3']);
        $validator = new RemoteAddr();
        self::assertEquals('1.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testShouldNotUseClientIpHeaderToTestProxyCapabilitiesByDefault(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        $_SERVER['HTTP_CLIENT_IP']       = '0.1.2.4';
        RemoteAddr::setUseProxy(true);
        $validator = new RemoteAddr();
        self::assertEquals('0.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testWillOmitTrustedProxyIpsFromXForwardedForMatching(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '1.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        RemoteAddr::setUseProxy(true);
        RemoteAddr::setTrustedProxies(['1.1.2.3']);
        $validator = new RemoteAddr();
        self::assertEquals('2.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testCanSpecifyWhichHeaderToUseStatically(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']          = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        $_SERVER['HTTP_CLIENT_IP']       = '0.1.2.4';
        RemoteAddr::setUseProxy(true);
        RemoteAddr::setProxyHeader('Client-Ip');
        $validator = new RemoteAddr();
        self::assertEquals('0.1.2.3', $validator->getData());
        $this->restore();
    }
}
