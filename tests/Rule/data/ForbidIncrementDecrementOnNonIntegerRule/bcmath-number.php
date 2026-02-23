<?php declare(strict_types = 1);

namespace ForbidIncrementDecrementOnNonIntegerRule;

use BcMath\Number;

class IncDecBcMathNumber {
    public function testPreDecrement(Number $x): void {
        --$x;
    }

    public function testPostDecrement(Number $x): void {
        $x--;
    }

    public function testPreIncrement(Number $x): void {
        ++$x;
    }

    public function testPostIncrement(Number $x): void {
        $x++;
    }
}
