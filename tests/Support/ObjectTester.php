<?php

declare(strict_types=1);

namespace Tests\Support;

use ReflectionClass;

class ObjectTester
{
    private $iClassObject;
    private $iObject;

    public function __construct($object)
    {
        $this->iObject      = $object;
        $this->iClassObject = new ReflectionClass($object);
    }

    public static function create($object)
    {
        return new self($object);
    }

    public function __get(string $name)
    {
        $iProp = $this->iClassObject->getProperty($name);

        $iProp->setAccessible(true);

        return $iProp->getValue($this->iObject);
    }

    public function __set(string $name, $value): void
    {
        $iProp = $this->iClassObject->getProperty($name);

        $iProp->setAccessible(true);

        $iProp->setValue($this->iObject, $value);
    }

    public function getObject()
    {
        return clone $this->iObject;
    }
}
