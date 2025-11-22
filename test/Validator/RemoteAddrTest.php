<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\RemoteAddr;
use LaminasTest\Session\TestAsset\TestCustomEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoteAddr::class)]
final class RemoteAddrTest extends TestCase
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
        $validator              = new RemoteAddr();
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        self::assertEquals('0.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testValidationSuccess(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        $this->expectNotToPerformAssertions();
        try {
            $validator->validate(new Environment(remoteAddr:  '0.1.2.3'), Environment::fromGlobals($_SERVER));
        } catch (SessionValidationFailedException $e) {
            $this->fail($e->getMessage());
        } finally {
            $this->restore();
        }
    }

    public function testValidationFailure(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        $this->expectException(SessionValidationFailedException::class);
        $this->expectExceptionMessage('Remote address validation failed');
        $validator->validate(new Environment(remoteAddr:  '0.1.1.3'), Environment::fromGlobals($_SERVER));
        $this->restore();
    }

    public function testIgnoreProxyByDefault(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR']    = '0.1.2.3';
        $_SERVER['HTTP_CLIENT_IP'] = '1.1.2.3';
        $validator                 = new RemoteAddr();
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        self::assertEquals('0.1.2.3', $validator->currentData);
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

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
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

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
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

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));

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

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        self::assertEquals('0.1.2.3', $validator->currentData);
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

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
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
            'use_proxy' => true,
        ];

        $validator = new RemoteAddr($options);
        $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        self::assertEquals('0.1.2.3', $validator->currentData);
        $this->restore();
    }

    public function testUnknownServerHeader(): void
    {
        $this->backup();
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'Unknow Header';

        $options = [
            'use_proxy' => true,
        ];

        $validator = new RemoteAddr($options);
        self::assertEmpty($validator->currentData);
        $this->restore();
    }

    public function testValidationSuccessWithCustomEnvironment(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        $this->expectNotToPerformAssertions();
        try {
            $validator->validate(
                new TestCustomEnvironment(
                    remoteAddr:  '0.1.2.3',
                    firstCustomProperty: 'fistCustomValue',
                    secondCustomProperty: 'secondCustomValue'
                ),
                TestCustomEnvironment::fromGlobals($_SERVER)
            );
        } catch (SessionValidationFailedException $e) {
            $this->fail($e->getMessage());
        } finally {
            $this->restore();
        }
    }

    public function testValidationFailureWithCustomEnvironment(): void
    {
        $this->backup();
        $_SERVER['REMOTE_ADDR'] = '0.1.2.3';
        $validator              = new RemoteAddr();
        $this->expectException(SessionValidationFailedException::class);
        $this->expectExceptionMessage('Remote address validation failed');
        $validator->validate(
            new TestCustomEnvironment(
                remoteAddr:  '0.1.1.3',
                firstCustomProperty: 'fistCustomValue',
                secondCustomProperty: 'secondCustomValue'
            ),
            TestCustomEnvironment::fromGlobals($_SERVER)
        );
        $this->restore();
    }
}
