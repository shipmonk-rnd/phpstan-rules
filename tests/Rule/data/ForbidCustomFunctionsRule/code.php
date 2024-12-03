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
     * @param class-string<SomeClass>|SomeClass $classStringOrTheClass
     * @param class-string<SomeClass> $classString
     */
    public function test(
        $classStringOrTheClass,
        string $classString,
        SomeClass $class,
        array $array,
        SomeClass|AnotherClass $union,
        ClassWithForbiddenAllMethods $forbiddenClass,
        ClassWithForbiddenConstructor $forbiddenConstructor,
        ChildOfClassWithForbiddenAllMethods $forbiddenClassChild,
        SomeInterface $interface
    ) {
        sleep(...); // error: Function sleep() is forbidden. Description 0
        sleep(0); // error: Function sleep() is forbidden. Description 0
        array_map('sleep', $array); // error: Function sleep() is forbidden. Description 0
        array_map(array: $array, callback: 'sleep'); // error: Function sleep() is forbidden. Description 0
        array_map([$class, 'forbiddenMethod'], $array); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        array_map([$class, 'forbiddenStaticMethod'], $array); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5

        strlen('sleep'); // not used in callable context
        [$class, 'forbiddenMethod']; // not used in callable context
        [$class, 'forbiddenStaticMethod']; // not used in callable context

        $this->acceptCallable('sleep', [], 'x');
        $this->acceptCallable('x', [], 'sleep'); // error: Function sleep() is forbidden. Description 0
        $this->acceptCallable(callable: 'sleep'); // error: Function sleep() is forbidden. Description 0
        $this->acceptCallable(string: 'sleep');
        $this->acceptCallable(callable: 'strlen', array: [], string: 'sleep');
        $this->acceptCallable('x', [], [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $this->acceptCallable(callable: [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        self::acceptCallableStatic('sleep', [], 'x');
        self::acceptCallableStatic('x', [], 'sleep'); // error: Function sleep() is forbidden. Description 0
        self::acceptCallableStatic(callable: 'sleep'); // error: Function sleep() is forbidden. Description 0
        self::acceptCallableStatic(string: 'sleep');
        self::acceptCallableStatic(callable: 'strlen', array: [], string: 'sleep');
        self::acceptCallableStatic('x', [], [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        self::acceptCallableStatic(callable: [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        $self = self::class;

        $self::acceptCallableStatic('sleep', [], 'x');
        $self::acceptCallableStatic('x', [], 'sleep'); // error: Function sleep() is forbidden. Description 0
        $self::acceptCallableStatic(callable: 'sleep'); // error: Function sleep() is forbidden. Description 0
        $self::acceptCallableStatic(string: 'sleep');
        $self::acceptCallableStatic(callable: 'strlen', array: [], string: 'sleep');
        $self::acceptCallableStatic('x', [], [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $self::acceptCallableStatic(callable: [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        new AcceptCallable('sleep', [], 'x');
        new AcceptCallable('x', [], 'sleep'); // error: Function sleep() is forbidden. Description 0
        new AcceptCallable(callable: 'sleep'); // error: Function sleep() is forbidden. Description 0
        new AcceptCallable(string: 'sleep');
        new AcceptCallable(callable: 'strlen', array: [], string: 'sleep');
        new AcceptCallable('x', [], [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        new AcceptCallable(callable: [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        $acceptCallableClass = AcceptCallable::class;

        new $acceptCallableClass('sleep', [], 'x');
        new $acceptCallableClass('x', [], 'sleep'); // error: Function sleep() is forbidden. Description 0
        new $acceptCallableClass(callable: 'sleep'); // error: Function sleep() is forbidden. Description 0
        new $acceptCallableClass(string: 'sleep');
        new $acceptCallableClass(callable: 'strlen', array: [], string: 'sleep');
        new $acceptCallableClass('x', [], [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        new $acceptCallableClass(callable: [$class, 'forbiddenMethod']); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        new class ('sleep', [], 'x') extends AcceptCallable {};
        new class ('x', [], 'sleep') extends AcceptCallable {}; // error: Function sleep() is forbidden. Description 0
        new class (callable: 'sleep') extends AcceptCallable {}; // error: Function sleep() is forbidden. Description 0
        new class (string: 'sleep') extends AcceptCallable {};
        new class (callable: 'strlen', array: [], string: 'sleep') extends AcceptCallable {};
        new class ('x', [], [$class, 'forbiddenMethod']) extends AcceptCallable {}; // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        new class (callable: [$class, 'forbiddenMethod']) extends AcceptCallable {}; // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4

        $class->allowedMethod();
        $class->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class?->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class->forbiddenMethod(...); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
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
        SomeClass::forbiddenStaticMethod(...); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5

        forbidden_namespaced_function(); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1
        forbidden_namespaced_function(...); // error: Function ForbidCustomFunctionsRule\forbidden_namespaced_function() is forbidden. Description 1

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
        $classStringOrTheClass::$forbiddenStaticMethodName(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5
        new $forbiddenClassName(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
    }

    private function acceptCallable(?string $string = null, ?array $array = null, ?callable $callable = null) {}
    private static function acceptCallableStatic(?string $string = null, ?array $array = null, ?callable $callable = null) {}

}

class AcceptCallable {
    public function __construct(?string $string = null, ?array $array = null, ?callable $callable = null) {}
}
