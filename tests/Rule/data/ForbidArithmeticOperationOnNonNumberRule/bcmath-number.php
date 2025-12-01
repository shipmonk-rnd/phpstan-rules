<?php declare(strict_types = 1);

namespace ForbidArithmeticOperationOnNonNumberRule;

use BcMath\Number;

class BcMathNumber {

    /**
     * @param numeric-string $ns
     */
    public function testBcMathNumbers(
        object $object,
        Number $number,
        string $ns,
        int $int,
        float $float,
        bool|float $boolFloat,
        int|float $intFloat,
        Number|int $nInt,
        Number|float $nFloat,
        Number|int|float $nIntFloat,
        Number|int|float|bool $nIntFloatBool
    ): void {
        +$number;
        -$number;

        $x = $object + $float; // error: Using + over non-number (object + float)
        $x = $object + $number; // error: Using + over non-number (object + BcMath\Number)

        $x = $number + $number;
        $x = $number - $number;
        $x = $number * $number;
        $x = $number / $number;

        $x = $number / $boolFloat; // error: Using / over BcMath\Number and float (BcMath\Number / bool|float)

        $x = $number + $int;
        $x = $int + $number;
        $x = $number + $ns;
        $x = $ns + $number;
        $x = $number - $int;
        $x = $int - $number;
        $x = $number - $ns;
        $x = $ns - $number;
        $x = $number * $int;
        $x = $int * $number;
        $x = $number * $ns;
        $x = $ns * $number;
        $x = $number / $int;
        $x = $int / $number;
        $x = $number / $ns;
        $x = $ns / $number;

        $x = $nInt + $int;
        $x = $int + $nInt;
        $x = $nInt + $ns;
        $x = $ns + $nInt;
        $x = $nInt - $int;
        $x = $int - $nInt;
        $x = $nInt - $ns;
        $x = $ns - $nInt;
        $x = $nInt * $int;
        $x = $int * $nInt;
        $x = $nInt * $ns;
        $x = $ns * $nInt;
        $x = $nInt / $int;
        $x = $int / $nInt;
        $x = $nInt / $ns;
        $x = $ns / $nInt;

        $x = $nFloat + $int;
        $x = $int + $nFloat;
        $x = $nFloat + $ns;
        $x = $ns + $nFloat;
        $x = $nFloat - $int;
        $x = $int - $nFloat;
        $x = $nFloat - $ns;
        $x = $ns - $nFloat;
        $x = $nFloat * $int;
        $x = $int * $nFloat;
        $x = $nFloat * $ns;
        $x = $ns * $nFloat;
        $x = $nFloat / $int;
        $x = $int / $nFloat;
        $x = $nFloat / $ns;
        $x = $ns / $nFloat;

        $x = $nIntFloat + $int;
        $x = $int + $nIntFloat;
        $x = $nIntFloat + $ns;
        $x = $ns + $nIntFloat;
        $x = $nIntFloat - $int;
        $x = $int - $nIntFloat;
        $x = $nIntFloat - $ns;
        $x = $ns - $nIntFloat;
        $x = $nIntFloat * $int;
        $x = $int * $nIntFloat;
        $x = $nIntFloat * $ns;
        $x = $ns * $nIntFloat;
        $x = $nIntFloat / $int;
        $x = $int / $nIntFloat;
        $x = $nIntFloat / $ns;
        $x = $ns / $nIntFloat;

        $x = $number + $float; // error: Using + over BcMath\Number and float (BcMath\Number + float)
        $x = $float + $number; // error: Using + over BcMath\Number and float (float + BcMath\Number)
        $x = $number - $float; // error: Using - over BcMath\Number and float (BcMath\Number - float)
        $x = $float - $number; // error: Using - over BcMath\Number and float (float - BcMath\Number)
        $x = $number * $float; // error: Using * over BcMath\Number and float (BcMath\Number * float)
        $x = $float * $number; // error: Using * over BcMath\Number and float (float * BcMath\Number)
        $x = $number / $float; // error: Using / over BcMath\Number and float (BcMath\Number / float)
        $x = $float / $number; // error: Using / over BcMath\Number and float (float / BcMath\Number)
        $x = $number % $float; // error: Using % over BcMath\Number and float (BcMath\Number % float)
        $x = $float % $number; // error: Using % over BcMath\Number and float (float % BcMath\Number)
        $x = $number ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number ** float)
        $x = $float ** $number; // error: Using ** over BcMath\Number and float (float ** BcMath\Number)
        $x = $number + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number + float|int)
        $x = $intFloat + $number; // error: Using + over BcMath\Number and float (float|int + BcMath\Number)

        $x = $nInt + $float; // error: Using + over BcMath\Number and float (BcMath\Number|int + float)
        $x = $float + $nInt; // error: Using + over BcMath\Number and float (float + BcMath\Number|int)
        $x = $nInt - $float; // error: Using - over BcMath\Number and float (BcMath\Number|int - float)
        $x = $float - $nInt; // error: Using - over BcMath\Number and float (float - BcMath\Number|int)
        $x = $nInt * $float; // error: Using * over BcMath\Number and float (BcMath\Number|int * float)
        $x = $float * $nInt; // error: Using * over BcMath\Number and float (float * BcMath\Number|int)
        $x = $nInt / $float; // error: Using / over BcMath\Number and float (BcMath\Number|int / float)
        $x = $float / $nInt; // error: Using / over BcMath\Number and float (float / BcMath\Number|int)
        $x = $nInt % $float; // error: Using % over BcMath\Number and float (BcMath\Number|int % float)
        $x = $float % $nInt; // error: Using % over BcMath\Number and float (float % BcMath\Number|int)
        $x = $nInt ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number|int ** float)
        $x = $float ** $nInt; // error: Using ** over BcMath\Number and float (float ** BcMath\Number|int)
        $x = $nInt + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number|int + float|int)
        $x = $intFloat + $nInt; // error: Using + over BcMath\Number and float (float|int + BcMath\Number|int)

        $x = $nFloat + $float; // error: Using + over BcMath\Number and float (BcMath\Number|float + float)
        $x = $float + $nFloat; // error: Using + over BcMath\Number and float (float + BcMath\Number|float)
        $x = $nFloat - $float; // error: Using - over BcMath\Number and float (BcMath\Number|float - float)
        $x = $float - $nFloat; // error: Using - over BcMath\Number and float (float - BcMath\Number|float)
        $x = $nFloat * $float; // error: Using * over BcMath\Number and float (BcMath\Number|float * float)
        $x = $float * $nFloat; // error: Using * over BcMath\Number and float (float * BcMath\Number|float)
        $x = $nFloat / $float; // error: Using / over BcMath\Number and float (BcMath\Number|float / float)
        $x = $float / $nFloat; // error: Using / over BcMath\Number and float (float / BcMath\Number|float)
        $x = $nFloat % $float; // error: Using % over BcMath\Number and float (BcMath\Number|float % float)
        $x = $float % $nFloat; // error: Using % over BcMath\Number and float (float % BcMath\Number|float)
        $x = $nFloat ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number|float ** float)
        $x = $float ** $nFloat; // error: Using ** over BcMath\Number and float (float ** BcMath\Number|float)
        $x = $nFloat + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number|float + float|int)
        $x = $intFloat + $nFloat; // error: Using + over BcMath\Number and float (float|int + BcMath\Number|float)

        $x = $nIntFloat + $float; // error: Using + over BcMath\Number and float (BcMath\Number|float|int + float)
        $x = $float + $nIntFloat; // error: Using + over BcMath\Number and float (float + BcMath\Number|float|int)
        $x = $nIntFloat - $float; // error: Using - over BcMath\Number and float (BcMath\Number|float|int - float)
        $x = $float - $nIntFloat; // error: Using - over BcMath\Number and float (float - BcMath\Number|float|int)
        $x = $nIntFloat * $float; // error: Using * over BcMath\Number and float (BcMath\Number|float|int * float)
        $x = $float * $nIntFloat; // error: Using * over BcMath\Number and float (float * BcMath\Number|float|int)
        $x = $nIntFloat / $float; // error: Using / over BcMath\Number and float (BcMath\Number|float|int / float)
        $x = $float / $nIntFloat; // error: Using / over BcMath\Number and float (float / BcMath\Number|float|int)
        $x = $nIntFloat % $float; // error: Using % over BcMath\Number and float (BcMath\Number|float|int % float)
        $x = $float % $nIntFloat; // error: Using % over BcMath\Number and float (float % BcMath\Number|float|int)
        $x = $nIntFloat ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number|float|int ** float)
        $x = $float ** $nIntFloat; // error: Using ** over BcMath\Number and float (float ** BcMath\Number|float|int)
        $x = $nIntFloat + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number|float|int + float|int)
        $x = $intFloat + $nIntFloat; // error: Using + over BcMath\Number and float (float|int + BcMath\Number|float|int)

        $x = $nIntFloatBool + $float; // error: Using + over BcMath\Number and float (BcMath\Number|bool|float|int + float)
        $x = $float + $nIntFloatBool; // error: Using + over BcMath\Number and float (float + BcMath\Number|bool|float|int)
        $x = $nIntFloatBool - $float; // error: Using - over BcMath\Number and float (BcMath\Number|bool|float|int - float)
        $x = $float - $nIntFloatBool; // error: Using - over BcMath\Number and float (float - BcMath\Number|bool|float|int)
        $x = $nIntFloatBool * $float; // error: Using * over BcMath\Number and float (BcMath\Number|bool|float|int * float)
        $x = $float * $nIntFloatBool; // error: Using * over BcMath\Number and float (float * BcMath\Number|bool|float|int)
        $x = $nIntFloatBool / $float; // error: Using / over BcMath\Number and float (BcMath\Number|bool|float|int / float)
        $x = $float / $nIntFloatBool; // error: Using / over BcMath\Number and float (float / BcMath\Number|bool|float|int)
        $x = $nIntFloatBool % $float; // error: Using % over BcMath\Number and float (BcMath\Number|bool|float|int % float)
        $x = $float % $nIntFloatBool; // error: Using % over BcMath\Number and float (float % BcMath\Number|bool|float|int)
        $x = $nIntFloatBool ** $float; // error: Using ** over BcMath\Number and float (BcMath\Number|bool|float|int ** float)
        $x = $float ** $nIntFloatBool; // error: Using ** over BcMath\Number and float (float ** BcMath\Number|bool|float|int)
        $x = $nIntFloatBool + $intFloat; // error: Using + over BcMath\Number and float (BcMath\Number|bool|float|int + float|int)
        $x = $intFloat + $nIntFloatBool; // error: Using + over BcMath\Number and float (float|int + BcMath\Number|bool|float|int)
    }
}

