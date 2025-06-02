<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

final class HttpUserAgent implements ValidatorInterface
{
    /**
     * Constructor
     * get the current user agent and store it in the session as 'valid data'
     */
    public function __construct(public readonly ?string $data = null)
    {
    }

    /**
     * isValid() - this method will determine if the current user agent matches the
     * user agent we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        $env = Environment::fromGlobals($_SERVER);
        return $env->userAgent === $this->data;
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
