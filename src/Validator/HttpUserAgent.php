<?php

namespace Laminas\Session\Validator;

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
     *
     * @return bool
     */
    public function isValid()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $userAgent === $this->getData();
    }

    /**
     * Retrieve token for validating call
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return validator name
     *
     * @return string
     */
    public function getName()
    {
        return self::class;
    }
}
