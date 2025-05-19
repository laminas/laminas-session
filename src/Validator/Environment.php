<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function is_string;

final class Environment
{
    public function __construct(public readonly ?string $userAgent)
    {
    }

    public static function fromGlobals(array $server): self
    {
        $userAgent = isset($server['HTTP_USER_AGENT']) && is_string($server['HTTP_USER_AGENT'])
            ? $server['HTTP_USER_AGENT']
            : null;

        return new self($userAgent);
    }
}
