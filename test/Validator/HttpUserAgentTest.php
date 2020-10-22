<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\HttpUserAgent;
use PHPUnit\Framework\TestCase;

class HttpUserAgentTest extends TestCase
{
    public function testIsValid(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test-User-Agent';

        $validator = new HttpUserAgent();

        self::assertNotNull($validator->getData());
        self::assertTrue($validator->isValid());
    }

    public function testIsValidWhenNoUserAgentIsSet(): void
    {
        // technically not needed in CLI
        unset($_SERVER['HTTP_USER_AGENT']);

        $validator = new HttpUserAgent();

        self::assertNull($validator->getData());
        self::assertTrue($validator->isValid());
    }

    public function testGetNameReturnsClassName(): void
    {
        $validator = new HttpUserAgent();

        self::assertSame(HttpUserAgent::class, $validator->getName());
    }
}
