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
        $initialServer      = ['HTTP_USER_AGENT' => 'Test-User-Agent'];
        $initialEnvironment = Environment::fromGlobals($initialServer);
        $currentEnvironment = new Environment('Test-User-Agent');

        $validator = new HttpUserAgent($initialEnvironment, $currentEnvironment);

        self::assertTrue($validator->isValid());
    }

    public function testIsValidWhenNoUserAgentIsSet(): void
    {
        // technically not needed in CLI
        $initialServer      = [];
        $initialEnvironment = Environment::fromGlobals($initialServer);
        $currentEnvironment = new Environment(null);
        $validator          = new HttpUserAgent($initialEnvironment, $currentEnvironment);

        self::assertTrue($validator->isValid());
    }

    public function testGetNameReturnsClassName(): void
    {
        $initialServer      = [];
        $initialEnvironment = Environment::fromGlobals($initialServer);
        $currentEnvironment = new Environment(null);
        $validator          = new HttpUserAgent($initialEnvironment, $currentEnvironment);

        self::assertSame(HttpUserAgent::class, $validator->getName());
    }
}
