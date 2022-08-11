<?php

namespace ForbidFetchOnMixedRuleTest;

use ReflectionClass;

$fn = function (mixed $mixed, $unknown, array $array, ReflectionClass $reflection) {
    $mixed->fetch1; // error: Property fetch ->fetch1 is prohibited on unknown type ($mixed)
    $unknown->fetch2; // error: Property fetch ->fetch2 is prohibited on unknown type ($unknown)
    $array[0]->fetch3; // error: Property fetch ->fetch3 is prohibited on unknown type ($array[0])
    $reflection->newInstance()->fetch4;
};
