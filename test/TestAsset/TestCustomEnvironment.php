<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\EnvironmentInterface;

use function assert;
use function is_string;
use function serialize;
use function session_id;
use function unserialize;

class TestCustomEnvironment implements EnvironmentInterface
{
    public function __construct(
        private readonly ?string $userAgent = null,
        private readonly ?string $remoteAddr = null,
        private readonly ?string $forwardedFor = null,
        private readonly ?string $sessionId = null,
        private readonly string $firstCustomProperty = 'fistCustomValue',
        private readonly string $secondCustomProperty = 'secondCustomValue',
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

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): TestCustomEnvironment
    {
        $environment = unserialize($data);
        assert($environment instanceof TestCustomEnvironment);
        return $environment;
    }

    public function __serialize(): array
    {
        return [
            'userAgent'            => $this->getUserAgent(),
            'remoteAddr'           => $this->getRemoteAddr(),
            'forwardedFor'         => $this->getForwardedFor(),
            'sessionId'            => $this->getSessionId(),
            'firstCustomProperty'  => $this->getFirstCustomProperty(),
            'secondCustomProperty' => $this->getSecondCustomProperty(),
        ];
    }

    public function __unserialize(array $data): void
    {
        assert(is_string($data['userAgent']) || $data['userAgent'] === null);
        assert(is_string($data['remoteAddr']) || $data['remoteAddr'] === null);
        assert(is_string($data['forwardedFor']) || $data['forwardedFor'] === null);
        assert(is_string($data['sessionId']) || $data['sessionId'] === null);
        assert(is_string($data['firstCustomProperty']));
        assert(is_string($data['secondCustomProperty']));

        $this->userAgent            = $data['userAgent'];
        $this->remoteAddr           = $data['remoteAddr'];
        $this->forwardedFor         = $data['forwardedFor'];
        $this->sessionId            = $data['sessionId'];
        $this->firstCustomProperty  = $data['firstCustomProperty'];
        $this->secondCustomProperty = $data['secondCustomProperty'];
    }
}
