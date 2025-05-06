<?php

namespace Laminas\Session\Service;

use Laminas\Session\Validator\Csrf;

final class CsrfValidatorFactory
{
    public function __invoke(): Csrf
    {
        return new Csrf();
    }
}
