<?php

namespace ForbidArithmeticOperationOnNonNumberRule;

class X {
    /**
     * @param numeric-string $ns
     */
    public function testVariableTypes(string $s, string $ns, int $i, float $f, array $a) {
        $x = $ns - 1; // error: Using - over non-number (string - int)
        $x = $ns + 1; // error: Using + over non-number (string + int)
        $x = $ns * 1; // error: Using * over non-number (string * int)
        $x = $ns / 1; // error: Using / over non-number (string / int)
        $x = $ns % 1; // error: Using % over non-integer (string % int)
        $x = $ns ** 1; // error: Using ** over non-number (string ** int)
        $x = +$ns; // error: Using + over non-number (string)
        $x = -$ns; // error: Using - over non-number (string)

        $x = $f - 1;
        $x = $f + 1;
        $x = $f * 1;
        $x = $f / 1;
        $x = $f % 1; // error: Using % over non-integer (float % int)
        $x = $f ** 1;
        $x = +$f;
        $x = -$f;

        $x = $i - 1;
        $x = $i + 1;
        $x = $i * 1;
        $x = $i / 1;
        $x = $i % 1;
        $x = $i ** 1;
        $x = +$i;
        $x = -$i;

        $x = $s - 1; // error: Using - over non-number (string - int)
        $x = $s + 1; // error: Using + over non-number (string + int)
        $x = $s * 1; // error: Using * over non-number (string * int)
        $x = $s / 1; // error: Using / over non-number (string / int)
        $x = $s % 1; // error: Using % over non-integer (string % int)
        $x = $s ** 1; // error: Using ** over non-number (string ** int)
        $x = +$s; // error: Using + over non-number (string)
        $x = -$s; // error: Using - over non-number (string)

        $x = $a - 1; // error: Using - over non-number (array - int)
        $x = $a + 1; // error: Using + over non-number (array + int)
        $x = $a * 1; // error: Using * over non-number (array * int)
        $x = $a / 1; // error: Using / over non-number (array / int)
        $x = $a % 1; // error: Using % over non-integer (array % int)
        $x = $a ** 1; // error: Using ** over non-number (array ** int)
        $x = +$a; // error: Using + over non-number (array)
        $x = -$a; // error: Using - over non-number (array)
    }
}
