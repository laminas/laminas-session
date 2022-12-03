<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\AbstractContainer;

/**
 * @template-covariant TKey of string
 * @template-covariant TValue
 * @template-extends AbstractContainer<TKey, TValue>
 */
class TestContainer extends AbstractContainer
{
    // do nothing
}
