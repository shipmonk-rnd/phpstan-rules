<?php declare(strict_types = 1);

namespace ForbidProtectedEnumMethodRule;

trait T {
    protected function foo() {}
}

interface I {
    public function bag();
}

enum Foo implements I {

    use T;

    protected function bar() {} // error: Protected methods within enum makes no sense as you cannot extend them anyway.
    public function bag() {}
    private function baz() {}
}

class Clazz implements I {
    use T;

    protected function bar() {}
    public function bag() {}
    private function baz() {}
}
