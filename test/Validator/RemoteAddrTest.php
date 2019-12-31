<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\RemoteAddr;

/**
 * @category   Laminas
 * @package    Laminas_Session
 * @subpackage UnitTests
 * @group      Laminas_Session
 */
class RemoteAddrTest extends \PHPUnit_Framework_TestCase
{
    protected $backup;

    protected function backup()
    {
        $this->backup = $_SERVER;
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CLIENT_IP']
        );
    }

    protected function restore()
    {
        $_SERVER = $this->backup;
    }

    public function testGetData()
    {
        $validator = new RemoteAddr('0.1.2.3');
        $this->assertEquals('0.1.2.3', $validator->getData());
    }

    public function testDefaultUseProxy()
    {
        $this->assertFalse(RemoteAddr::getUseProxy());
    }

    public function testRemoteAddrWithoutProxy()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator = new RemoteAddr();
        $this->assertEquals('0.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testIsValid()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator = new RemoteAddr();
        $_SERVER['REMOTE_ADDR'] = '1.1.2.3';
        $this->assertFalse($validator->isValid());
        $this->restore();
    }

    public function testIgnoreProxyByDefault()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $_SERVER['HTTP_CLIENT_IP'] = '1.1.2.3';
        $validator = new RemoteAddr();
        $this->assertEquals('0.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testHttpXForwardedFor()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.2.3';
        RemoteAddr::setUseProxy(true);
        $validator = new RemoteAddr();
        RemoteAddr::setUseProxy(false);
        $this->assertEquals('1.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testHttpClientIp()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3';
        RemoteAddr::setUseProxy(true);
        $validator = new RemoteAddr();
        RemoteAddr::setUseProxy(false);
        $this->assertEquals('2.1.2.3', $validator->getData());
        $this->restore();
    }

    public function testMultipleHttpXForwardedFor()
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.1.2.3, 1.1.2.3';
        RemoteAddr::setUseProxy(true);
        $validator = new RemoteAddr();
        RemoteAddr::setUseProxy(false);
        $this->assertEquals('2.1.2.3', $validator->getData());
        $this->restore();
    }
}
