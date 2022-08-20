<?php

namespace ForbidMethodCallOnMixedRule;

use ReflectionClass;

$fn = function (mixed $mixed, $unknown, array $array, ReflectionClass $reflection) {
    $mixed->call1(); // error: Method call ->call1() is prohibited on unknown type ($mixed)
    $unknown->call2(); // error: Method call ->call2() is prohibited on unknown type ($unknown)
    $array[0]->call3(); // error: Method call ->call3() is prohibited on unknown type ($array[0])
    $reflection->newInstance()->call4();
};
