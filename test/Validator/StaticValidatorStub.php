<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\ValidatorInterface;

/**
 * @implements ValidatorInterface<false>
 */
class StaticValidatorStub implements ValidatorInterface
{
    public static int $isValidCallCount = 0;

    public function isValid(): bool
    {
        self::$isValidCallCount++;
        return $this->getData();
    }

    public function getData(): mixed
    {
        return false;
    }

    public function getName(): string
    {
        return self::class;
    }
}
