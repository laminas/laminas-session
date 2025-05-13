<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

/**
 * @implements ValidatorInterface<string>
 */
class HttpUserAgent implements ValidatorInterface
{
    /**
     * Internal data
     *
     * @var string
     */
    protected $data;

    /**
     * Constructor
     * get the current user agent and store it in the session as 'valid data'
     *
     * @param string|null $data
     */
    public function __construct($data = null)
    {
        if ($data === null || $data === '') {
            $data = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }
        $this->data = $data;
    }

    /**
     * isValid() - this method will determine if the current user agent matches the
     * user agent we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $userAgent === $this->getData();
    }

    /**
     * Retrieve token for validating call
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
