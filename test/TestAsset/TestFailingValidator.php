<?php

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
