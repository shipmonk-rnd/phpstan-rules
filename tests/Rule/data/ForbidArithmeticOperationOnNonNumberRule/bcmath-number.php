<?php declare(strict_types = 1);

namespace ForbidArithmeticOperationOnNonNumberRule;
use BcMath\Number;

class BcMathNumber {

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
        $x = $n + $ns;
        $x = $ns + $n;
        $x = $n - $int;
        $x = $int - $n;
        $x = $n - $ns;
        $x = $ns - $n;
        $x = $n * $int;
        $x = $int * $n;
        $x = $n * $ns;
        $x = $ns * $n;
        $x = $n / $int;
        $x = $int / $n;
        $x = $n / $ns;
        $x = $ns / $n;

        $x = $n + $float; // error: Using + over BcMath\Number and float (BcMath\Number + float)
        $x = $float + $n; // error: Using + over BcMath\Number and float (float + BcMath\Number)
        $x = $n - $float; // error: Using - over BcMath\Number and float (BcMath\Number - float)
        $x = $float - $n; // error: Using - over BcMath\Number and float (float - BcMath\Number)
        $x = $n * $float; // error: Using * over BcMath\Number and float (BcMath\Number * float)
        $x = $float * $n; // error: Using * over BcMath\Number and float (float * BcMath\Number)
        $x = $n / $float; // error: Using / over BcMath\Number and float (BcMath\Number / float)
        $x = $float / $n; // error: Using / over BcMath\Number and float (float / BcMath\Number)
        $x = $n % $float; // error: Using % over BcMath\Number and float (BcMath\Number % float)
        $x = $float % $n; // error: Using % over BcMath\Number and float (float % BcMath\Number)
        $x = $n ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number ** float)
        $x = $float ** $n; // error: Using ** over BcMath\Number and float (float ** BcMath\Number)

        $x = $n + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number + float|int)
        $x = $intFloat + $n; // error: Using + over BcMath\Number and float (float|int + BcMath\Number)
    }
}

