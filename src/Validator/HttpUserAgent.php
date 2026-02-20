<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;

use function sprintf;

final class HttpUserAgent implements ValidatorInterface
{
    /**
     * This method will determine if the current user agent matches the
     * user agent we stored when we initialized this variable.
     *
     * @throws SessionValidationFailedException
     */
    public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void
    {
        if ($initial->getUserAgent() !== $current->getUserAgent()) {
            throw new SessionValidationFailedException(sprintf(
                'Http user agent validation failed. Expected %s, got %s.',
                (string) $initial->getUserAgent(),
                (string) $current->getUserAgent()
            ));
        }
    }
}
