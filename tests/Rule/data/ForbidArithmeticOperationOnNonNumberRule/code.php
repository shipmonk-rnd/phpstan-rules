<?php declare(strict_types = 1);

namespace ForbidArithmeticOperationOnNonNumberRule;

class Ari {

    public function testConstantTypes() {
        $x = '4.1' - 1;
        $x = '4.1' + 1;
        $x = '4.1' * 1;
        $x = '4.1' / 1;
        $x = '4.1' % 1; // error: Using % over non-integer (string % int)
        $x = '4.1' ** 1;
        $x = +'4.1';
        $x = -'4.1';

        $x = 4.1 - 1;
        $x = 4.1 + 1;
        $x = 4.1 * 1;
        $x = 4.1 / 1;
        $x = 4.1 % 1; // error: Using % over non-integer (float % int)
        $x = 4.1 ** 1;
        $x = +4.1;
        $x = -4.1;

        $x = 4 - 1;
        $x = 4 + 1;
        $x = 4 * 1;
        $x = 4 / 1;
        $x = 4 % 1;
        $x = 4 ** 1;
        $x = +4;
        $x = -4;

        $x = '4a' - 1; // error: Using - over non-number (string - int)
        $x = '4a' + 1; // error: Using + over non-number (string + int)
        $x = '4a' * 1; // error: Using * over non-number (string * int)
        $x = '4a' / 1; // error: Using / over non-number (string / int)
        $x = '4a' % 1; // error: Using % over non-integer (string % int)
        $x = '4a' ** 1; // error: Using ** over non-number (string ** int)
        $x = +'4a'; // error: Using + over non-number (string)
        $x = -'4a'; // error: Using - over non-number (string)

        $x = 'aa' - 1; // error: Using - over non-number (string - int)
        $x = 'aa' + 1; // error: Using + over non-number (string + int)
        $x = 'aa' * 1; // error: Using * over non-number (string * int)
        $x = 'aa' / 1; // error: Using / over non-number (string / int)
        $x = 'aa' % 1; // error: Using % over non-integer (string % int)
        // $x = 'aa' ** 1; https://github.com/phpstan/phpstan/issues/10125
        $x = +'aa'; // error: Using + over non-number (string)
        $x = -'aa'; // error: Using - over non-number (string)

        $x = [] - 1; // error: Using - over non-number (array - int)
        $x = [] + 1; // error: Using + over non-number (array + int)
        $x = [] * 1; // error: Using * over non-number (array * int)
        $x = [] / 1; // error: Using / over non-number (array / int)
        $x = [] % 1; // error: Using % over non-integer (array % int)
        $x = [] ** 1; // error: Using ** over non-number (array ** int)
        $x = +[]; // error: Using + over non-number (array)
        $x = -[]; // error: Using - over non-number (array)
    }

    /**
     * @param numeric-string $ns
     */
    public function testVariableTypes(string $s, string $ns, int $i, float $f, array $a) {
        $x = $ns - 1;
        $x = $ns + 1;
        $x = $ns * 1;
        $x = $ns / 1;
        $x = $ns % 1; // error: Using % over non-integer (string % int)
        $x = $ns ** 1;
        $x = +$ns;
        $x = -$ns;

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

    public function testArrays(array $a, array $b)
    {
        $x = $a - $b; // error: Using - over non-number (array - array)
        $x = $a + $b;
        $x = $a * $b; // error: Using * over non-number (array * array)
        $x = $a / $b; // error: Using / over non-number (array / array)
        $x = $a % $b; // error: Using % over non-integer (array % array)
        $x = $a ** $b; // error: Using ** over non-number (array ** array)
    }

    public function testUnions(
        int|string $intString,
        int|float $intFloat,
        int|float|string $intFloatString,
        int|array $intArray
    ) {
        -$intString; // error: Using - over non-number (int|string)
        -$intFloat;
        -$intFloatString; // error: Using - over non-number (float|int|string)
        -$intArray; // error: Using - over non-number (array|int)
    }

    /**
     * @param positive-int $int
     * @param positive-int|float $intFloat
     * @param int-mask<1, 2, 4> $intMask
     */
    public function testSpecialTypes(int $int, int|float $intFloat, int $intMask)
    {
        -$int;
        -$intFloat;
        -$intMask;
    }

}

