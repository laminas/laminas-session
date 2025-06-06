<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\ValidatorInterface;

class StaticValidatorStub implements ValidatorInterface
{
    public function __construct(Environment $initial, Environment $current)
    {
    }

    public static int $isValidCallCount = 0;
    public ?Environment $current        = null;

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
