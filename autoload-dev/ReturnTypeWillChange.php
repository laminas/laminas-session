<?php
declare(strict_types=1);

if (\PHP_VERSION_ID < 80100) {
    #[Attribute(Attribute::TARGET_METHOD)]
    final class ReturnTypeWillChange
    {
    }
}
