<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\ValidatorInterface;

final class TestFailingValidator implements ValidatorInterface
{
    public function __construct(Environment $initial, Environment $current, array $options = [])
    {
    }

    public ?Environment $current = null;
    public function getName(): string
    {
        return self::class;
    }

    public function isValid(): bool
    {
        return false;
    }
}
