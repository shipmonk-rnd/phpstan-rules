<?php

namespace ForbidUnsetClassFieldRule;

class Clazz {

    public ?int $foo;

    public function __construct(int $foo) {
        $this->foo = $foo;
    }

}

$class = new Clazz(1);
unset($class->foo); // error: Unsetting class field is forbidden as it causes un-initialization, assign null instead
echo $class->foo;
