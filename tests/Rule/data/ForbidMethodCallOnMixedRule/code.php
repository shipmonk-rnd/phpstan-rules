<?php

namespace ForbidMethodCallOnMixedRule;

use ReflectionClass;

class Foo {
    public function method() {}
    public static function staticMethod() {}
}

$fn = function (mixed $mixed, $unknown, array $array, string $string, ?Foo $fooOrNull, ReflectionClass $reflection) {

    $foo = new Foo();
    $foo->method();
    $foo?->method();
    Foo::staticMethod();
    $foo::staticMethod();

    $fooOrNull->method();
    $fooOrNull?->method();
    $fooOrNull->staticMethod();
    $fooOrNull?->staticMethod();

    /** @var class-string $classString */
    $classString = '';
    $classString::staticMethod(); // error: Method call ::staticMethod() is prohibited on unknown type ($classString)

    /** @var class-string<Foo> $classString2 */
    $classString2 = '';
    $classString2::staticMethod();

    $string::staticMethod(); // error: Method call ::staticMethod() is prohibited on unknown type ($string)

    $mixed->call1(); // error: Method call ->call1() is prohibited on unknown type ($mixed)
    $mixed?->call1(); // error: Method call ?->call1() is prohibited on unknown type ($mixed)
    $mixed::call1(); // error: Method call ::call1() is prohibited on unknown type ($mixed)
    $unknown->call2(); // error: Method call ->call2() is prohibited on unknown type ($unknown)
    $unknown?->call2(); // error: Method call ?->call2() is prohibited on unknown type ($unknown)
    $unknown::call2(); // error: Method call ::call2() is prohibited on unknown type ($unknown)
    $array[0]->call3(); // error: Method call ->call3() is prohibited on unknown type ($array[0])


    $reflection->newInstance()->method(); // error: Method call ->method() is prohibited on unknown type ($reflection->newInstance())
    /** @var ReflectionClass<Foo> $reflection */
    $reflection->newInstance()->method();
};
