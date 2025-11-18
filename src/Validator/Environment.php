<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function assert;
use function is_string;
use function serialize;
use function session_id;
use function unserialize;

final class Environment implements EnvironmentInterface
{
    public function __construct(
        private readonly ?string $userAgent = null,
        private readonly ?string $remoteAddr = null,
        private readonly ?string $forwardedFor = null,
        private readonly ?string $sessionId = null
    ) {
    }

    public static function fromGlobals(array $data): self
    {
        $userAgent = isset($data['HTTP_USER_AGENT']) && is_string($data['HTTP_USER_AGENT'])
            ? $data['HTTP_USER_AGENT']
            : null;

        $remoteAddr = isset($data['REMOTE_ADDR']) && is_string($data['REMOTE_ADDR'])
            ? $data['REMOTE_ADDR']
            : null;

        $forwardedFor = isset($data['HTTP_X_FORWARDED_FOR']) && is_string($data['HTTP_X_FORWARDED_FOR'])
            ? $data['HTTP_X_FORWARDED_FOR']
            : null;

        $sessionId = session_id();

        return new self($userAgent, $remoteAddr, $forwardedFor, $sessionId !== false ? $sessionId : null);
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getRemoteAddr(): ?string
    {
        return $this->remoteAddr;
    }

    public function getForwardedFor(): ?string
    {
        return $this->forwardedFor;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function serialize(): string
    {
        return serialize($this);
    }

    public function unserialize(string $data): Environment
    {
        $environment = unserialize($data);
        assert($environment instanceof Environment);
        return $environment;
    }

    public function __serialize(): array
    {
        return [
            'userAgent'    => $this->getUserAgent(),
            'remoteAddr'   => $this->getRemoteAddr(),
            'forwardedFor' => $this->getForwardedFor(),
            'sessionId'    => $this->getSessionId(),
        ];
    }

    public function __unserialize(array $data): void
    {
        assert(is_string($data['userAgent']) || $data['userAgent'] === null);
        assert(is_string($data['remoteAddr']) || $data['remoteAddr'] === null);
        assert(is_string($data['forwardedFor']) || $data['forwardedFor'] === null);
        assert(is_string($data['sessionId']) || $data['sessionId'] === null);

        $this->userAgent    = $data['userAgent'];
        $this->remoteAddr   = $data['remoteAddr'];
        $this->forwardedFor = $data['forwardedFor'];
        $this->sessionId    = $data['sessionId'];
    }
}
