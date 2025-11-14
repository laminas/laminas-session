<?php

declare(strict_types=1);

namespace Laminas\Session\Exception;

use Psr\SimpleCache\CacheException;
use RuntimeException;

final class SimpleCacheException extends RuntimeException implements CacheException
{
}
