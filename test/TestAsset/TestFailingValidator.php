<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\Validator\EnvironmentInterface;
use Laminas\Session\Validator\ValidatorInterface;

final class TestFailingValidator implements ValidatorInterface
{
    public function __construct(array $options = [])
    {
    }

    public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void
    {
        throw new SessionValidationFailedException('Validation failed');
    }
}
