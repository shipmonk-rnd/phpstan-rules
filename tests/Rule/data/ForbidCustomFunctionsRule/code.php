<?php declare(strict_types = 1);

namespace ForbidCustomFunctionsRule;

class SomeClass implements SomeInterface {
    public function allowedMethod() {}
    public function forbiddenMethod() {}
    public static function forbiddenStaticMethod() {}

    public function allowedInterfaceMethod() {}
    public function forbiddenInterfaceMethod() {}
    public static function forbiddenInterfaceStaticMethod() {}
}

class ClassWithForbiddenAllMethods {

    public function foo() {}
    public function bar() {}
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


$fn = function (
    SomeClass $class1,
    ClassWithForbiddenAllMethods $class2,
    ClassWithForbiddenConstructor $class3,
    SomeInterface $interface
) {
    sleep(0); // error: Function sleep() is forbidden. Description 0

    $class1->allowedMethod();
    $class1->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
    $class1->allowedInterfaceMethod();
    $class1->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() is forbidden. Description 6

    $class2->foo(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2
    $class2->bar(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2

    new ClassWithForbiddenConstructor(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
    new ClassWithForbiddenAllMethods(); // error: Class ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods is forbidden. Description 2

    $interface->allowedInterfaceMethod();
    $interface->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() is forbidden. Description 6

    SomeClass::forbiddenInterfaceStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceStaticMethod() is forbidden. Description 7
    SomeClass::forbiddenStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5

    forbidden_namespaced_function(); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1

    $forbiddenMethodName = 'forbiddenMethod';
    $forbiddenGlobalFunctionName = 'sleep';
    $forbiddenFunctionName = 'ForbidCustomFunctionsRule\forbidden_namespaced_function';

    $forbiddenGlobalFunctionName(); // error: Function sleep() is forbidden. Description 0
    $forbiddenFunctionName(); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1
    $class1->$forbiddenMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
};
