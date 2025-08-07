<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function is_string;
use function session_id;

final class Environment
{
    public function __construct(
        public readonly ?string $userAgent = null,
        public readonly ?string $remoteAddr = null,
        public readonly ?string $forwardedFor = null,
        public readonly ?string $sessionId = null
    ) {
    }

    public static function fromGlobals(array $server): self
    {
        $userAgent = isset($server['HTTP_USER_AGENT']) && is_string($server['HTTP_USER_AGENT'])
            ? $server['HTTP_USER_AGENT']
            : null;

        $remoteAddr = isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR'])
            ? $server['REMOTE_ADDR']
            : null;

        $forwardedFor = isset($server['HTTP_X_FORWARDED_FOR']) && is_string($server['HTTP_X_FORWARDED_FOR'])
            ? $server['HTTP_X_FORWARDED_FOR']
            : null;

        $sessionId = session_id();

        return new self($userAgent, $remoteAddr, $forwardedFor, $sessionId);
    }
}
