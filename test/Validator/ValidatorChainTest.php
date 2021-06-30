<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Validator\ValidatorInterface;
use Laminas\Session\ValidatorChain;
use LaminasTest\Session\TestAsset\TestFailingValidator;
use PHPUnit\Framework\TestCase;

use function assert;
use function get_class;
use function property_exists;

class ValidatorChainTest extends TestCase
{
    /** @var ValidatorChain */
    private $validatorChain;

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
        $validator = $this->createValidatorSpy();
        $storage   = new ArrayStorage();
        $storage->setMetadata('_VALID', [get_class($validator) => $validator->getData()]);

        $this->validatorChain = new ValidatorChain($storage);

        $this->validatorChain->trigger('session.validate');
        assert(property_exists($validator, 'isValidCallCount'));
        self::assertSame(1, $validator::$isValidCallCount);
    }

    private function createValidatorSpy(): ValidatorInterface
    {
        return new class implements ValidatorInterface {
            /** @var int */
            public static $isValidCallCount = 0;

            public function isValid(): bool
            {
                self::$isValidCallCount++;
                return $this->getData();
            }

            public function getData(): bool
            {
                return false;
            }

            public function getName(): string
            {
                return self::class;
            }
        };
    }
}
