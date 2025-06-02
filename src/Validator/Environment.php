<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function is_string;

final class Environment
{
    public function __construct(
        public readonly ?string $userAgent = null,
        public readonly ?string $remoteAddr = null
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

        return new self($userAgent, $remoteAddr);
    }

    public static function getServerOption(string $name, ?array $superglobal = null): mixed
    {
        if ($superglobal === null) {
            $superglobal = $_SERVER;
        }

        return $superglobal[$name] ?? null;
    }
}
