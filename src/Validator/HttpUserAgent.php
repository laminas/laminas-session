<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

final class HttpUserAgent implements ValidatorInterface
{
    /**
     * Constructor
     * get the current user agent and store it in the session as 'valid data'
     */
    public function __construct(private readonly Environment $initial, private readonly Environment $current)
    {
    }

    /**
     * isValid() - this method will determine if the current user agent matches the
     * user agent we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        return $this->initial->userAgent === $this->current->userAgent;
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
