<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\HttpUserAgent;
use LaminasTest\Session\TestAsset\TestCustomEnvironment;
use PHPUnit\Framework\TestCase;

final class HttpUserAgentTest extends TestCase
{
    public function testIsValid(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test-User-Agent';
        $validator                  = new HttpUserAgent();

        $this->expectNotToPerformAssertions();
        try {
            $validator->validate(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        } catch (SessionValidationFailedException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidWithCustomEnvironment(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test-User-Agent';
        $validator                  = new HttpUserAgent();

        $this->expectNotToPerformAssertions();
        try {
            $validator->validate(
                TestCustomEnvironment::fromGlobals($_SERVER),
                TestCustomEnvironment::fromGlobals($_SERVER)
            );
        } catch (SessionValidationFailedException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsValidWhenNoUserAgentIsSet(): void
    {
        // technically not needed in CLI
        unset($_SERVER['HTTP_USER_AGENT']);
        $validator = new HttpUserAgent();

        $this->expectNotToPerformAssertions();
        try {
            $validator->validate(new Environment(userAgent: null), Environment::fromGlobals($_SERVER));
        } catch (SessionValidationFailedException $e) {
            $this->fail($e->getMessage());
        }
    }
}
