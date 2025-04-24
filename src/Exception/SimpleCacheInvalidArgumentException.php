<?php

declare(strict_types=1);

namespace Laminas\Session\Exception;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class SimpleCacheInvalidArgumentException extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
