<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\HttpUserAgent;
use PHPUnit\Framework\TestCase;

class HttpUserAgentTest extends TestCase
{
    public function testIsValid(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test-User-Agent';
        $validator                  = new HttpUserAgent(
            Environment::fromGlobals($_SERVER),
            Environment::fromGlobals($_SERVER)
        );

        self::assertTrue($validator->isValid());
    }

    public function testIsValidWhenNoUserAgentIsSet(): void
    {
        // technically not needed in CLI
        unset($_SERVER['HTTP_USER_AGENT']);
        $validator = new HttpUserAgent(new Environment(userAgent: null), Environment::fromGlobals($_SERVER));

        self::assertTrue($validator->isValid());
    }

    public function testGetNameReturnsClassName(): void
    {
        $validator = new HttpUserAgent(new Environment(userAgent: null), Environment::fromGlobals($_SERVER));

        self::assertSame(HttpUserAgent::class, $validator->getName());
    }
}
