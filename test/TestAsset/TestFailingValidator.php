<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\ValidatorInterface;

class TestFailingValidator implements ValidatorInterface
{

    public function getData()
    {
        return false;
    }

    public function getName()
    {
        return __CLASS__;
    }

    public function isValid()
    {
        return $this->getData();
    }
}
