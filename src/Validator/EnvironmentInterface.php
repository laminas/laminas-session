<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Serializable;

interface EnvironmentInterface extends Serializable
{
    public function getUserAgent(): ?string;

    public function getRemoteAddr(): ?string;

    public function getForwardedFor(): ?string;

    public function getSessionId(): ?string;
}
