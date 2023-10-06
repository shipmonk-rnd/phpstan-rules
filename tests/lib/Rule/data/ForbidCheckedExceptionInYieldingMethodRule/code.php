<?php declare(strict_types = 1);

namespace ForbidCheckedExceptionInYieldingMethodRule;

use Generator;
use LogicException;
use RuntimeException;

class CheckedException extends RuntimeException {}

class A {

    /**
     * @return iterable<int>
     * @throws CheckedException
     */
    public static function throwPointOfYieldingMethod(bool $throw): iterable
    {
        yield 1;
        if ($throw) {
            throw new CheckedException(); // error: Throwing checked exception ForbidCheckedExceptionInYieldingMethodRule\CheckedException in yielding method is denied as it gets thrown upon Generator iteration
        }
    }

    /**
     * @return iterable<int>
     * @throws CheckedException
     */
    private static function throwPointOfNonYieldingMethod(bool $throw): iterable
    {
        if ($throw) {
            throw new CheckedException();
        }

        return [2];
    }

    /**
     * @return Generator<int>
     */
    private static function methodWithUncheckedException(bool $throw): Generator
    {
        if ($throw) {
            throw new LogicException();
        }

        yield 3;
    }

    /**
     * @throws CheckedException
     * @return Generator<int>
     */
    public static function testIt(bool $throw): Generator
    {
        yield from self::throwPointOfYieldingMethod($throw); // error: Throwing checked exception ForbidCheckedExceptionInYieldingMethodRule\CheckedException in yielding method is denied as it gets thrown upon Generator iteration
        yield from self::throwPointOfNonYieldingMethod($throw); // error: Throwing checked exception ForbidCheckedExceptionInYieldingMethodRule\CheckedException in yielding method is denied as it gets thrown upon Generator iteration
        yield from self::methodWithUncheckedException($throw);
    }

}

