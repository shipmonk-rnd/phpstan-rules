<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule\data\ForbidArithmeticOperationOnNonNumberRule;
use BcMath\Number;

class BcMathNumberNoNumeric {

    /**
     * @param numeric-string $ns
     */
    public function testBcMathNumbers(Number $n, string $ns, int $int, float $float, int|float $intFloat): void {
        +$n;
        -$n;

        $x = $n + $n;
        $x = $n - $n;
        $x = $n * $n;
        $x = $n / $n;

        $x = $n + $int;
        $x = $int + $n;
        $x = $n + $ns; // error: Using + over non-number (BcMath\Number + string)
        $x = $ns + $n; // error: Using + over non-number (string + BcMath\Number)
        $x = $n - $int;
        $x = $int - $n;
        $x = $n - $ns; // error: Using - over non-number (BcMath\Number - string)
        $x = $ns - $n; // error: Using - over non-number (string - BcMath\Number)
        $x = $n * $int;
        $x = $int * $n;
        $x = $n * $ns; // error: Using * over non-number (BcMath\Number * string)
        $x = $ns * $n; // error: Using * over non-number (string * BcMath\Number)
        $x = $n / $int;
        $x = $int / $n;
        $x = $n / $ns; // error: Using / over non-number (BcMath\Number / string)
        $x = $ns / $n; // error: Using / over non-number (string / BcMath\Number)
    }
}

