<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function is_string;
use function session_id;

/** @psalm-import-type OptionsArgument from RemoteAddr */
final class Environment
{
    public function __construct(
        public readonly ?string $userAgent = null,
        public readonly ?string $remoteAddr = null,
        public readonly ?string $sessionId = null
    ) {
    }

    /** @param OptionsArgument $options */
    public static function fromGlobals(array $server, array $options = []): self
    {
        $userAgent = isset($server['HTTP_USER_AGENT']) && is_string($server['HTTP_USER_AGENT'])
            ? $server['HTTP_USER_AGENT']
            : null;

        $remoteAddr = isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR'])
            ? $server['REMOTE_ADDR']
            : null;

        if ($remoteAddr === null || (isset($options['use_proxy']) && $options['use_proxy'])) {
            $remoteAddr = RemoteAddr::getIpAddress($options, $remoteAddr);
        }

        $sessionId = session_id();

        return new self($userAgent, $remoteAddr, $sessionId);
    }

    public static function getServerOption(string $name, ?array $superglobal = null): mixed
    {
        if ($superglobal === null) {
            $superglobal = $_SERVER;
        }

        return $superglobal[$name] ?? null;
    }
}
