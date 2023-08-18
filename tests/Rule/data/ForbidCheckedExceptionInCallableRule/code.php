<?php

namespace ForbidCheckedExceptionInCallableRule;

class CheckedException extends \Exception {}

/**
 * @throws CheckedException
 */
function throwing_function() {}

class FirstClassCallableTest {

    public function test(): void
    {
        $this->noop(...);

        $this->throws(...); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!

        // $this?->throws(...); // https://github.com/phpstan/phpstan/issues/9746

        throwing_function(...); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!

        $this->denied($this->throws(...)); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in first-class-callable!

        $this->immediateThrow($this->throws(...));

        ($this->throws(...))();

        (throwing_function(...))();

        array_map($this->throws(...), []);

        array_map(throwing_function(...), []);

        $this->allowThrow($this->throws(...));

        $this->allowThrow(throwing_function(...));
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

class ClosureTest {

    public function test(): void
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

        $this->denied(function () {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        });

        $this?->denied(function () {
            $this->throws(); // error: Throwing checked exception ForbidCheckedExceptionInCallableRule\CheckedException in closure!
        });

        $this->immediateThrow(function () {
            throw new CheckedException();
        });

        $self = $this; // self is unknown variable in scope of the closure
        $self->immediateThrow(function () {
            throw new CheckedException();
        });

        (function () {
            throw new CheckedException();
        })();

        array_map(function () {
            throw new CheckedException();
        }, []);

        array_map(function () {
            $this->throws();
        }, []);

        $this->allowThrow(function () {
            $this->throws();
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
