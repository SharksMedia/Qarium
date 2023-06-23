<?php

declare(strict_types=1);

namespace Tests\Support;

use ReflectionClass;
use Sharksmedia\Objection\Model;
use stdClass;

class ModelTester
{
    private          $iClassObject;
    private          $iObject;

    public function __construct(string $objectClass, ...$constructorArgs)
    {
        $this->iObject = new $objectClass(...$constructorArgs);
        $this->iClassObject = new ReflectionClass($objectClass);
    }

    public static function create(string $objectClass, ...$constructorArgs)
    {
        return new self($objectClass, ...$constructorArgs);
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

