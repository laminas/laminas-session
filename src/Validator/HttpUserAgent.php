<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;

final class HttpUserAgent implements ValidatorInterface
{
    /**
     * Constructor
     * get the current user agent and store it in the session as 'valid data'
     */
    public function __construct(array $options = [])
    {
    }

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
