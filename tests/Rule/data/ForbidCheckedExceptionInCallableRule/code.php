<?php

namespace ForbidCheckedExceptionInCallableRule;

class CheckedException extends \Exception {}

/**
 * @throws CheckedException
 */
function throwing_function() {}

function allowed_function(callable $callable) {}

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

    /**
     * @param-immediately-invoked-callable $callable
     */
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

        $this->immediateThrow(
            denied: function () {},
        );

        $this->immediateThrow(
            denied: function () {
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

    /**
     * @param-immediately-invoked-callable $callable
     */
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

class ArrowFunctionTest extends BaseCallableTest {

    public function __construct($callable)
    {
        new self(fn () => throw new CheckedException());
    }

    public function testDeclarations(): void
    {
        $fn = fn () => throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!

        $fn2 = fn () => $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!

        $fn3 = fn () => $this->noop(); // implicit throw is ignored

        $fn4 = fn (callable $c) => $c(); // implicit throw is ignored (https://github.com/phpstan/phpstan/issues/9779)
    }

    public function testExplicitExecution(): void
    {
        (fn ()  => throw new CheckedException())();
    }

    public function testPassedCallbacks(): void
    {
        $this->immediateThrow(fn ()  => throw new CheckedException());

        array_map(fn () => throw new CheckedException(), []);

        array_map(fn () => $this->throws(), []);

        $this->allowThrow(fn () => $this->throws());

        $this->allowThrowInBaseClass(fn () => $this->throws());

        $this->allowThrowInInterface(fn () => $this->throws());

        $this->denied(fn () => throw new CheckedException()); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!

        $this?->denied(fn () => throw new CheckedException()); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!
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

    /**
     * @param-immediately-invoked-callable $callable
     */
    public function immediateThrow(callable $callable): void
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

class ArgumentSwappingTest {

    public function test()
    {
        $this->call(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            $this->throws(...),
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
        );

        $this->call(
            second: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            first: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->throws(...),
        );

        $this->call(
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            first: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
        );

        $this->call(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            second: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->throws(...),
        );

        $this->call(
            $this->noop(...),
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->noop(...),
        );

        // this is not yet supported, the rule do not see this as argument pass
        $this->call(... [
            'third' => $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            'first' => $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            'second' => $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
        ]);
    }

    /**
     * @param-immediately-invoked-callable $third
     */
    public function call(
        callable $first,
        ?callable $second = null,
        ?callable $third = null,
        ?callable $forth = null
    ): void
    {

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
}
