<?php declare(strict_types = 1);

namespace ForbidCustomFunctionsRule;

abstract class SomeParent {
    public abstract function forbiddenMethodOfParent();
}

class SomeClass extends SomeParent implements SomeInterface {
    public function allowedMethod() {}
    public function forbiddenMethod() {}
    public function forbiddenMethodOfParent() {}
    public static function forbiddenStaticMethod() {}

    public function allowedInterfaceMethod() {}
    public function forbiddenInterfaceMethod() {}
    public static function forbiddenInterfaceStaticMethod() {}

    public function getSelf(): self { return $this; }
}

class AnotherClass {
    public function forbiddenMethod() {}
}

class ClassWithForbiddenAllMethods {

    public function foo() {}
    public function bar() {}
}

class ChildOfClassWithForbiddenAllMethods extends ClassWithForbiddenAllMethods {

    public function baz() {}
}

class ClassWithForbiddenConstructor {

    public function foo() {}
}

interface SomeInterface {
    public function allowedInterfaceMethod();
    public function forbiddenInterfaceMethod();
    public static function forbiddenInterfaceStaticMethod();
}

function forbidden_namespaced_function() {}

class Test
{

    /**
     * @param class-string<SomeClass> $classString
     */
    public function test(
        string $classString,
        SomeClass $class,
        SomeClass|AnotherClass $union,
        ClassWithForbiddenAllMethods $forbiddenClass,
        ClassWithForbiddenConstructor $forbiddenConstructor,
        ChildOfClassWithForbiddenAllMethods $forbiddenClassChild,
        SomeInterface $interface
    ) {
        sleep(0); // error: Function sleep() is forbidden. Description 0

        $class->allowedMethod();
        $class->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class->allowedInterfaceMethod();
        $class->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() is forbidden. Description 6
        $class->forbiddenMethodOfParent(); // error: Method ForbidCustomFunctionsRule\SomeParent::forbiddenMethodOfParent() is forbidden. Description 8

        $forbiddenClass->foo(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2
        $forbiddenClass->bar(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2
        $forbiddenClassChild->baz(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2

        $forbiddenConstructor->foo();
        $union->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        new ClassWithForbiddenConstructor(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
        new ClassWithForbiddenAllMethods(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2

        $interface->allowedInterfaceMethod();
        $interface->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() is forbidden. Description 6

        SomeClass::forbiddenInterfaceStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceStaticMethod() is forbidden. Description 7
        SomeClass::forbiddenStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5

        forbidden_namespaced_function(); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1

        $forbiddenClassName = 'ForbidCustomFunctionsRule\ClassWithForbiddenConstructor';
        $forbiddenMethodName = 'forbiddenMethod';
        $forbiddenStaticMethodName = 'forbiddenStaticMethod';
        $forbiddenGlobalFunctionName = 'sleep';
        $forbiddenFunctionName = 'ForbidCustomFunctionsRule\forbidden_namespaced_function';

        $forbiddenGlobalFunctionName(); // error: Function sleep() is forbidden. Description 0
        $forbiddenFunctionName(); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1
        $class->$forbiddenMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class::$forbiddenStaticMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5
        $class->getSelf()->$forbiddenMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class->getSelf()::$forbiddenStaticMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5
        $classString::$forbiddenStaticMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5
        new $forbiddenClassName(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
    }
}
