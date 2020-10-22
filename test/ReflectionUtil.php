<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use ReflectionObject;

final class ReflectionUtil
{
    /**
     * @return mixed
     */
    public static function getProperty(object $object, string $property)
    {
        $reflectionObject   = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $reflectionProperty->setAccessible(false);
        return $value;
    }
}
