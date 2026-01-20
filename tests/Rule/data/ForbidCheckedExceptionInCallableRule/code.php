<?php

namespace ForbidCheckedExceptionInCallableRule;

class CheckedException extends \Exception {}

/**
 * @throws CheckedException
 */
function throwing_function() {}

function allowed_function(callable $callable) {}

/**
 * @param-later-invoked-callable $callable
 */
function allowed_function_not_immediate(callable $callable) {}

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

    public function testDeclarations1(): void
    {
        $this->noop(...);
    }

    public function testDeclarations2(): void
    {
        $this->throws(...); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
    }

    public function testDeclarations3(): void
    {
        // $this?->throws(...); // https://github.com/phpstan/phpstan/issues/9746
    }

    public function testDeclarations4(): void
    {
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

    public function testPassedCallbacksA1(): void
    {
        $this->immediateThrow(null, $this->throws(...));
    }

    public function testPassedCallbacksA2(): void
    {
        array_map($this->throws(...), []);
    }

    public function testPassedCallbacksA3(): void
    {
        array_map(throwing_function(...), []);
    }

    public function testPassedCallbacksA4(): void
    {
        $this->allowThrow(42, $this->throws(...));
    }

    public function testPassedCallbacksA5(): void
    {
        $this->allowThrow(42, throwing_function(...));
    }

    public function testPassedCallbacksA6(): void
    {
        allowed_function(throwing_function(...));
        allowed_function_not_immediate(throwing_function(...));
    }

    public function testPassedCallbacksA6(): void
    {
        $this->immediateThrow(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            function () {},
        );
    }

    public function testPassedCallbacksA7(): void
    {
        $this->allowThrowInBaseClass(throwing_function(...));
    }

    public function testPassedCallbacksA8(): void
    {
        $this->allowThrowInInterface(throwing_function(...));
    }

    public function testPassedCallbacksA9(): void
    {
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

    public function testDeclarationsB1(): void
    {
        $fn = function () {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        };
    }

    public function testDeclarationsB2(): void
    {
        $fn2 = function () {
            $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        };
    }

    public function testDeclarationsB3(): void
    {
        $fn3 = function () {
            $this->noop(); // implicit throw is ignored
        };
    }

    public function testDeclarationsB4(): void
    {
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

    public function testPassedCallbacks1(): void
    {
        $this->immediateThrow(function () {
            throw new CheckedException();
        });
    }

    public function testPassedCallbacks2(): void
    {
        $self = $this; // self is unknown variable in scope of the closure
        $self->immediateThrow(function () {
            throw new CheckedException();
        });
    }

    public function testPassedCallbacks3(): void
    {
        array_map(function () {
            throw new CheckedException();
        }, []);
    }

    public function testPassedCallbacks4(): void
    {
        array_map(function () {
            $this->throws();
        }, []);
    }

    public function testPassedCallbacks5(): void
    {
        $this->allowThrow(function () {
            $this->throws();
        });
    }

    public function testPassedCallbacks6(): void
    {
        $this->immediateThrow(
            function () {},
            function () {
                throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
            },
        );
    }

    public function testPassedCallbacks7(): void
    {
        $this->immediateThrow(
            denied: function () {},
        );
    }

    public function testPassedCallbacks8(): void
    {
        // Note: This should report an error because 'denied' is not @param-immediately-invoked-callable,
        // but PHPStan's getFunctionCallStackWithParameters() incorrectly reports the parameter as 'callable'
        // when using named arguments. PHPStan bug fix: https://github.com/phpstan/phpstan-src/pull/4791
        $this->immediateThrow(
            denied: function () {
                throw new CheckedException();
            },
        );
    }

    public function testPassedCallbacks9(): void
    {
        $this->allowThrowInBaseClass(function () {
            $this->throws();
        });
    }

    public function testPassedCallbacks10(): void
    {
        $this->allowThrowInInterface(function () {
            $this->throws();
        });
    }

    public function testPassedCallbacks11(): void
    {
        $this->denied(function () {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        });
    }

    public function testPassedCallbacks12(): void
    {
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

    public function testDeclarationsC1(): void
    {
        $fn = fn () => throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!
    }

    public function testDeclarationsC2(): void
    {
        $fn2 = fn () => $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!
    }

    public function testDeclarationsC3(): void
    {
        $fn3 = fn () => $this->noop(); // implicit throw is ignored
    }

    public function testDeclarationsC4(): void
    {
        $fn4 = fn (callable $c) => $c(); // implicit throw is ignored (https://github.com/phpstan/phpstan/issues/9779)
    }

    public function testExplicitExecution(): void
    {
        (fn ()  => throw new CheckedException())();
    }

    public function testPassedCallbacksC1(): void
    {
        $this->immediateThrow(fn ()  => throw new CheckedException());
    }

    public function testPassedCallbacksC2(): void
    {
        array_map(fn () => throw new CheckedException(), []);
    }

    public function testPassedCallbacksC3(): void
    {
        array_map(fn () => $this->throws(), []);
    }

    public function testPassedCallbacksC4(): void
    {
        $this->allowThrow(fn () => $this->throws());
    }

    public function testPassedCallbacksC5(): void
    {
        $this->allowThrowInBaseClass(fn () => $this->throws());
    }

    public function testPassedCallbacksC6(): void
    {
        $this->allowThrowInInterface(fn () => $this->throws());
    }

    public function testPassedCallbacksC7(): void
    {
        $this->denied(fn () => throw new CheckedException()); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in arrow function!
    }

    public function testPassedCallbacksC8(): void
    {
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

    public function testD1(): void
    {
        $this->call(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            $this->throws(...),
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
        );
    }

    public function testD2(): void
    {
        $this->call(
            second: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            first: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->throws(...),
        );
    }

    public function testD3(): void
    {
        $this->call(
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            first: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
        );
    }

    public function testD4(): void
    {
        $this->call(
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            second: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->throws(...),
        );
    }

    public function testD5(): void
    {
        $this->call(
            $this->noop(...),
            $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            forth: $this->throws(...), // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!
            third: $this->noop(...),
        );
    }

    public function testD6(): void
    {
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


