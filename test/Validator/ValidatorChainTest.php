<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Validator\Environment;
use Laminas\Session\ValidatorChain;
use LaminasTest\Session\TestAsset\TestFailingValidator;
use PHPUnit\Framework\TestCase;

use function serialize;

class ValidatorChainTest extends TestCase
{
    private ValidatorChain $validatorChain;

    protected function setUp(): void
    {
        $storage              = new ArrayStorage();
        $this->validatorChain = new ValidatorChain($storage);
    }

    public function testGetStorage(): void
    {
        self::assertInstanceOf(ArrayStorage::class, $this->validatorChain->getStorage());
    }

    public function testAttachValidator(): void
    {
        $validator = new TestFailingValidator(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));

        $this->validatorChain->attach('test', [$validator, 'isValid']);

        $validatorMetadata = $this->validatorChain->getStorage()->getMetadata('_VALID');
        self::assertIsArray($validatorMetadata);
    }

    public function testExistingValidatorsAreAttached(): void
    {
        $validator = new StaticValidatorStub(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));
        $storage   = new ArrayStorage();
        $storage->setMetadata('_VALID', [$validator::class => null]);
        $storage->setMetadata('environment', serialize(Environment::fromGlobals($_SERVER)));
        $this->validatorChain = new ValidatorChain($storage);

        $this->validatorChain->trigger('session.validate');
        self::assertSame(1, $validator::$isValidCallCount);
    }
}
