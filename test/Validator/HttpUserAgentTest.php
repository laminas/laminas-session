<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\EnvironmentValueObject;
use Laminas\Session\Validator\HttpUserAgent;
use PHPUnit\Framework\TestCase;

class HttpUserAgentTest extends TestCase
{
    protected EnvironmentValueObject $environment;

    public function setUp(): void
    {
        $this->environment = EnvironmentValueObject::fromGlobals();
    }

    public function testIsValid(): void
    {
        $this->environment->setHttpUserAgent('Test-User-Agent');
        $validator = new HttpUserAgent($this->environment);

        self::assertNotNull($validator->getData());
        self::assertTrue($validator->isValid());
    }

    public function testIsValidWhenNoUserAgentIsSet(): void
    {
        // technically not needed in CLI
        $this->environment->setHttpUserAgent(null);
        $validator = new HttpUserAgent($this->environment);

        self::assertNull($validator->getData());
        self::assertTrue($validator->isValid());
    }

    public function testGetNameReturnsClassName(): void
    {
        $validator = new HttpUserAgent($this->environment);

        self::assertSame(HttpUserAgent::class, $validator->getName());
    }
}
