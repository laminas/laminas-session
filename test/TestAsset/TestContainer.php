<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\AbstractContainer;

/**
 * @template TKey of string
 * @template TValue
 * @template-extends AbstractContainer<TKey, TValue>
 */
class TestContainer extends AbstractContainer
{
    // do nothing
}
