<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\EnvironmentInterface;

use function is_string;
use function session_id;

class TestCustomEnvironment implements EnvironmentInterface
{
    public function __construct(
        public readonly ?string $userAgent = null,
        public readonly ?string $remoteAddr = null,
        public readonly ?string $forwardedFor = null,
        public readonly ?string $sessionId = null,
        public readonly string $firstCustomProperty = 'fistCustomValue',
        public readonly string $secondCustomProperty = 'secondCustomValue',
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

        $firstCustomProperty  = 'fistCustomValue';
        $secondCustomProperty = 'secondCustomValue';

        return new self(
            $userAgent,
            $remoteAddr,
            $forwardedFor,
            $sessionId,
            $firstCustomProperty,
            $secondCustomProperty
        );
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

    public function getFirstCustomProperty(): string
    {
        return $this->firstCustomProperty;
    }

    public function getSecondCustomProperty(): string
    {
        return $this->secondCustomProperty;
    }
}
