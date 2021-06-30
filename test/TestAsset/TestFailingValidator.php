<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Validator\ValidatorInterface;

class TestFailingValidator implements ValidatorInterface
{
    /** @return bool */
    public function getData()
    {
        return false;
    }

    /** @return string */
    public function getName()
    {
        return self::class;
    }

    /** @return bool */
    public function isValid()
    {
        return $this->getData();
    }
}
