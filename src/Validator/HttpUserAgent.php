<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;

final class HttpUserAgent implements ValidatorInterface
{
    /**
     * isValid() - this method will determine if the current user agent matches the
     * user agent we stored when we initialized this variable.
     *
     * @throws SessionValidationFailedException
     */
    public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void
    {
        if ($initial->getUserAgent() !== $current->getUserAgent()) {
            throw new SessionValidationFailedException('Http user agent validation failed');
        }
    }
}
