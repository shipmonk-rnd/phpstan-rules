<?php

namespace ForbidCheckedExceptionInCallableRule;

class CheckedException extends \Exception {}

/**
 * @throws CheckedException
 */
function throwing_function() {}

interface CallableTest {

    public function allowThrowInInterface(callable $callable): void;

}

class BaseCallableTest implements CallableTest {

    public function allowThrowInInterface(callable $callable): void
    {
        try {
            $callable();
        } catch (\Exception $e) {

        }
    }

    public function allowThrowInBaseClass(callable $callable): void
    {
        try {
            $callable();
        } catch (\Exception $e) {

        }
    }

}

class FirstClassCallableTest extends BaseCallableTest {

    public function testDeclarations(): void
    {
        $this->noop(...);

        $this->throws(...); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!

        // $this?->throws(...); // https://github.com/phpstan/phpstan/issues/9746

        throwing_function(...); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
    }

    public function testExplicitExecution1(): void
    {
        ($this->throws(...))();
    }

    public function testExplicitExecution2(): void
    {
        (throwing_function(...))();
    }

    public function testPassedCallbacks(): void
    {
        $this->immediateThrow(null, $this->throws(...));

        array_map($this->throws(...), []);

        array_map(throwing_function(...), []);

        $this->allowThrow(42, $this->throws(...));

        $this->allowThrow(42, throwing_function(...));

        $this->immediateThrow(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            function () {},
        );

        $this->allowThrowInBaseClass(throwing_function(...));

        $this->allowThrowInInterface(throwing_function(...));

        $this->denied($this->throws(...)); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
    }

    private function noop(): void
    {
    }

    /**
     * @throws CheckedException
     */
    private function throws(): void
    {
        throw new CheckedException();
    }

    private function denied(callable $callable): void
    {

    }

    public function immediateThrow(?callable $denied, callable $callable): void
    {
        $callable();
    }

    public function allowThrow(int $dummy, callable $callable): void
    {
        try {
            $callable();
        } catch (\Exception $e) {

        }
    }

}

class ClosureTest extends BaseCallableTest {

    public function testDeclarations(): void
    {
        $fn = function () {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        };

        $fn2 = function () {
            $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        };

        $fn3 = function () {
            $this->noop(); // implicit throw is ignored
        };

        $fn4 = function (callable $c) {
            $c(); // implicit throw is ignored (https://github.com/phpstan/phpstan/issues/9779)
        };
    }

    public function testExplicitExecution(): void
    {
        (function () {
            throw new CheckedException();
        })();
    }

    public function testPassedCallbacks(): void
    {
        $this->immediateThrow(function () {
            throw new CheckedException();
        });

        $self = $this; // self is unknown variable in scope of the closure
        $self->immediateThrow(function () {
            throw new CheckedException();
        });

        array_map(function () {
            throw new CheckedException();
        }, []);

        array_map(function () {
            $this->throws();
        }, []);

        $this->allowThrow(function () {
            $this->throws();
        });

        $this->immediateThrow(
            function () {},
            function () {
                throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
            },
        );

        $this->allowThrowInBaseClass(function () {
            $this->throws();
        });

        $this->allowThrowInInterface(function () {
            $this->throws();
        });

        $this->denied(function () {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        });

        $this?->denied(function () {
            $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        });
    }

    private function noop(): void
    {
    }

    /**
     * @throws CheckedException
     */
    private function throws(): void
    {
        throw new CheckedException();
    }

    private function denied(callable $callable): void
    {

    }

    public function immediateThrow(callable $callable, ?callable $denied = null): void
    {
        $callable();
    }

    public function allowThrow(callable $callable): void
    {
        try {
            $callable();
        } catch (\Exception $e) {

        }
    }

}
