<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\ValidatorChain;
use LaminasTest\Session\TestAsset\TestFailingValidator;
use PHPUnit\Framework\TestCase;

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
        $validator = new TestFailingValidator();

        $this->validatorChain->attach('test', [$validator, 'isValid']);

        $validatorMetadata = $this->validatorChain->getStorage()->getMetadata('_VALID');
        self::assertIsArray($validatorMetadata);
        self::assertArrayHasKey($validator->getName(), $validatorMetadata);
        self::assertSame($validatorMetadata[$validator->getName()], $validator->getData());
    }

    public function testExistingValidatorsAreAttached(): void
    {
        $validator = new StaticValidatorStub();
        $storage   = new ArrayStorage();
        $storage->setMetadata('_VALID', [$validator::class => $validator->getData()]);

        $this->validatorChain = new ValidatorChain($storage);

        $this->validatorChain->trigger('session.validate');
        self::assertSame(1, $validator::$isValidCallCount);
    }
}
