<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

class EnvironmentValueObject
{
    public function __construct(protected ?string $httpUserAgent = null)
    {
    }

    public static function fromGlobals(): self
    {
        return new self($_SERVER['HTTP_USER_AGENT'] ?? null);
    }

    public function getHttpUserAgent(): ?string
    {
        return $this->httpUserAgent;
    }

    public function setHttpUserAgent(?string $httpUserAgent): void
    {
        $this->httpUserAgent = $httpUserAgent;
    }
}
