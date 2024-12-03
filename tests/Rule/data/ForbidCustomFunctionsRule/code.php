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
        sleep(...);
        sleep(0); // error: Method sleep() is forbidden. Description 0
        array_map('sleep', $array);
        array_map(array: $array, callback: 'sleep');
        array_map([$class, 'forbiddenMethod'], $array);
        array_map([$class, 'forbiddenStaticMethod'], $array);

        strlen('sleep'); // not used in callable context
        [$class, 'forbiddenMethod']; // not used in callable context
        [$class, 'forbiddenStaticMethod']; // not used in callable context

        $this->acceptCallable('sleep', [], 'x');
        $this->acceptCallable('x', [], 'sleep');
        $this->acceptCallable(callable: 'sleep');
        $this->acceptCallable(string: 'sleep');
        $this->acceptCallable(callable: 'strlen', array: [], string: 'sleep');
        $this->acceptCallable('x', [], [$class, 'forbiddenMethod']);
        $this->acceptCallable(callable: [$class, 'forbiddenMethod']);

        self::acceptCallableStatic('sleep', [], 'x');
        self::acceptCallableStatic('x', [], 'sleep');
        self::acceptCallableStatic(callable: 'sleep');
        self::acceptCallableStatic(string: 'sleep');
        self::acceptCallableStatic(callable: 'strlen', array: [], string: 'sleep');
        self::acceptCallableStatic('x', [], [$class, 'forbiddenMethod']);
        self::acceptCallableStatic(callable: [$class, 'forbiddenMethod']);

        $self = self::class;

        $self::acceptCallableStatic('sleep', [], 'x');
        $self::acceptCallableStatic('x', [], 'sleep');
        $self::acceptCallableStatic(callable: 'sleep');
        $self::acceptCallableStatic(string: 'sleep');
        $self::acceptCallableStatic(callable: 'strlen', array: [], string: 'sleep');
        $self::acceptCallableStatic('x', [], [$class, 'forbiddenMethod']);
        $self::acceptCallableStatic(callable: [$class, 'forbiddenMethod']);

        new AcceptCallable('sleep', [], 'x');
        new AcceptCallable('x', [], 'sleep');
        new AcceptCallable(callable: 'sleep');
        new AcceptCallable(string: 'sleep');
        new AcceptCallable(callable: 'strlen', array: [], string: 'sleep');
        new AcceptCallable('x', [], [$class, 'forbiddenMethod']);
        new AcceptCallable(callable: [$class, 'forbiddenMethod']);

        $acceptCallableClass = AcceptCallable::class;

        new $acceptCallableClass('sleep', [], 'x');
        new $acceptCallableClass('x', [], 'sleep');
        new $acceptCallableClass(callable: 'sleep');
        new $acceptCallableClass(string: 'sleep');
        new $acceptCallableClass(callable: 'strlen', array: [], string: 'sleep');
        new $acceptCallableClass('x', [], [$class, 'forbiddenMethod']);
        new $acceptCallableClass(callable: [$class, 'forbiddenMethod']);

        new class ('sleep', [], 'x') extends AcceptCallable {};
        new class ('x', [], 'sleep') extends AcceptCallable {};
        new class (callable: 'sleep') extends AcceptCallable {};
        new class (string: 'sleep') extends AcceptCallable {};
        new class (callable: 'strlen', array: [], string: 'sleep') extends AcceptCallable {};
        new class ('x', [], [$class, 'forbiddenMethod']) extends AcceptCallable {};
        new class (callable: [$class, 'forbiddenMethod']) extends AcceptCallable {};

        $class->allowedMethod();
        $class->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class?->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() is forbidden. Description 4
        $class->forbiddenMethod(...);
        $class->allowedInterfaceMethod();
        $class->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() (as ForbidCustomFunctionsRule\SomeClass::forbiddenInterfaceMethod()) is forbidden. Description 6
        $class->forbiddenMethodOfParent();

        $forbiddenClass->foo(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::foo() is forbidden. Description 2. [ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::foo() matches ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::*()]
        $forbiddenClass->bar(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::bar() is forbidden. Description 2. [ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::bar() matches ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::*()]
        $forbiddenClassChild->baz();

        $forbiddenConstructor->foo();
        $union->forbiddenMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenMethod() (as {ForbidCustomFunctionsRule\SomeClass,ForbidCustomFunctionsRule\AnotherClass}::forbiddenMethod()) is forbidden. Description 4

        new class {};
        new class extends ClassWithForbiddenConstructor {};
        new class extends ClassWithForbiddenAllMethods {};
        new ClassWithForbiddenConstructor(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
        new ClassWithForbiddenAllMethods(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::__construct() is forbidden. Description 2. [ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::__construct() matches ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::*()]

        $interface->allowedInterfaceMethod();
        $interface->forbiddenInterfaceMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod() is forbidden. Description 6

        SomeClass::forbiddenInterfaceStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceStaticMethod() (as ForbidCustomFunctionsRule\SomeClass::forbiddenInterfaceStaticMethod()) is forbidden. Description 7
        SomeClass::forbiddenStaticMethod(); // error: Method ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod() is forbidden. Description 5
        SomeClass::forbiddenStaticMethod(...);

        forbidden_namespaced_function();
        forbidden_namespaced_function(...);

        $forbiddenClassName = 'ForbidCustomFunctionsRule\ClassWithForbiddenConstructor';
        $forbiddenMethodName = 'forbiddenMethod';
        $forbiddenStaticMethodName = 'forbiddenStaticMethod';
        $forbiddenGlobalFunctionName = 'sleep';
        $forbiddenFunctionName = 'ForbidCustomFunctionsRule\forbidden_namespaced_function';

        $forbiddenGlobalFunctionName();
        $forbiddenFunctionName();
        $class->$forbiddenMethodName();
        $class::$forbiddenStaticMethodName();
        $class->getSelf()->$forbiddenMethodName();
        $class->getSelf()::$forbiddenStaticMethodName();
        $classString::$forbiddenStaticMethodName();
        $classStringOrTheClass::$forbiddenStaticMethodName();
        new $forbiddenClassName(); // error: Method ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct() is forbidden. Description 3
    }

    private function acceptCallable(?string $string = null, ?array $array = null, ?callable $callable = null) {}
    private static function acceptCallableStatic(?string $string = null, ?array $array = null, ?callable $callable = null) {}

}

class AcceptCallable {
    public function __construct(?string $string = null, ?array $array = null, ?callable $callable = null) {}
}
