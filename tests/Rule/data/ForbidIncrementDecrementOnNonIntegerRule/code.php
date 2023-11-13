<?php declare(strict_types = 1);

namespace ForbidIncrementDecrementOnNonIntegerRule;


class IncDec
{

    public function test(int $int, string $string, float $float, int|string $union, array $array, $mixed)
    {
        $int++;
        $string++; // error: Using ++ over non-integer (string)
        $float++; // error: Using ++ over non-integer (float)
        $union++; // error: Using ++ over non-integer (int|string)
        $array++; // error: Using ++ over non-integer (array)
        $mixed++; // error: Using ++ over non-integer (mixed)
    }
}


