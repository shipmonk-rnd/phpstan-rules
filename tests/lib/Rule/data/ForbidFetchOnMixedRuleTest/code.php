<?php

namespace ForbidFetchOnMixedRuleTest;

use ReflectionClass;

class Foo {
    public const CONST = 1;

    public ?int $property = null;
    public static ?int $staticProperty = null;
}

$fn = function (mixed $mixed, $unknown, string $string, array $array, ReflectionClass $reflection, ?Foo $fooOrNull, object $object) {
    (new Foo)->property;
    Foo::$staticProperty;
    Foo::CONST;
    Foo::class;

    /** @var class-string $classString */
    $classString = '';
    $classString::$staticProperty; // error: Property fetch ::$staticProperty is prohibited on unknown type ($classString)
    $classString::CONST; // error: Constant fetch ::CONST is prohibited on unknown type ($classString)

    /** @var class-string<Foo> $classString2 */
    $classString2 = '';
    $classString2::$staticProperty;
    $classString2::CONST;

    $string::$staticProperty; // error: Property fetch ::$staticProperty is prohibited on unknown type ($string)

    $mixed->fetch1; // error: Property fetch ->fetch1 is prohibited on unknown type ($mixed)
    $mixed::$fetch1; // error: Property fetch ::$fetch1 is prohibited on unknown type ($mixed)
    $mixed::CONST; // error: Constant fetch ::CONST is prohibited on unknown type ($mixed)
    $mixed::class; // error: Constant fetch ::class is prohibited on unknown type ($mixed)
    $unknown->fetch2; // error: Property fetch ->fetch2 is prohibited on unknown type ($unknown)
    $unknown::$fetch2; // error: Property fetch ::$fetch2 is prohibited on unknown type ($unknown)
    $array[0]->fetch3; // error: Property fetch ->fetch3 is prohibited on unknown type ($array[0])

    $fooOrNull->property;
    $fooOrNull?->property;
    $fooOrNull::$staticProperty;
    $fooOrNull::CONST;
    $fooOrNull::class;

    $object::class;
    $object::CONST; // error: Constant fetch ::CONST is prohibited on unknown type ($object)

    $reflection->newInstance()->property; // error: Property fetch ->property is prohibited on unknown type ($reflection->newInstance())
    $reflection->newInstance()::CONST; // error: Constant fetch ::CONST is prohibited on unknown type ($reflection->newInstance())
    /** @var ReflectionClass<Foo> $reflection */
    $reflection->newInstance()->property;
    $reflection->newInstance()::CONST;
};
