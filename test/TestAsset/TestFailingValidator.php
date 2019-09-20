<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\TestAsset;

use Zend\Session\Validator\ValidatorInterface;

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
