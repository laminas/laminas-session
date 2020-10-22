<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use ReflectionObject;

trait ReflectionPropertyTrait
{
    /**
     * @return mixed
     */
    private function getReflectionProperty(object $object, string $property)
    {
        $reflectionObject   = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }
}
