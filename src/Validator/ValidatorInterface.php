<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;

/**
 * Session validator interface
 */
interface ValidatorInterface
{
    /** @throws SessionValidationFailedException */
    public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void;
}
