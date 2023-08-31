<?php

namespace ForbidFetchOnMixedRuleTest;

use ReflectionClass;

class Foo {
    public ?int $property = null;
    public static ?int $staticProperty = null;
}

$fn = function (mixed $mixed, $unknown, array $array, ReflectionClass $reflection) {
    (new Foo)->property;
    Foo::$staticProperty;

    $mixed->fetch1; // error: Property fetch ->fetch1 is prohibited on unknown type ($mixed)
    $mixed::$fetch1; // error: Property fetch ::$fetch1 is prohibited on unknown type ($mixed)
    $unknown->fetch2; // error: Property fetch ->fetch2 is prohibited on unknown type ($unknown)
    $unknown::$fetch2; // error: Property fetch ::$fetch2 is prohibited on unknown type ($unknown)
    $array[0]->fetch3; // error: Property fetch ->fetch3 is prohibited on unknown type ($array[0])
    $reflection->newInstance()->fetch4;
};
