<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\EnvironmentInterface;
use Laminas\Session\Validator\ValidatorInterface;

final class TestFailingValidator implements ValidatorInterface
{
    public function __construct(EnvironmentInterface $initial, EnvironmentInterface $current, array $options = [])
    {
    }

    public ?EnvironmentInterface $current = null;
    public function getName(): string
    {
        return self::class;
    }

    public function isValid(): bool
    {
        return false;
    }
}
