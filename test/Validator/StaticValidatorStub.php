<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\ValidatorInterface;

class StaticValidatorStub implements ValidatorInterface
{
    public static int $isValidCallCount = 0;
    public ?string $data                = null;

    public function isValid(): bool
    {
        self::$isValidCallCount++;
        return false;
    }

    public function getName(): string
    {
        return self::class;
    }
}
