<?php

namespace ForbidNullInAssignOperatorsRule;

function testErrors(
    int $int,
    ?int $intOrNull,
    string $string,
    ?string $stringOrNull,
) {
    $int += $intOrNull; // error: Null value involved in += assignment on the right side.
    $int -= $intOrNull; // error: Null value involved in -= assignment on the right side.
    $int *= $intOrNull; // error: Null value involved in *= assignment on the right side.
    $int /= $intOrNull; // error: Null value involved in /= assignment on the right side.
    $int %= $intOrNull; // error: Null value involved in %= assignment on the right side.
    $int **= $intOrNull; // error: Null value involved in **= assignment on the right side.
    $int &= $intOrNull; // error: Null value involved in &= assignment on the right side.
    $int |= $intOrNull; // error: Null value involved in |= assignment on the right side.
    $int ^= $intOrNull; // error: Null value involved in ^= assignment on the right side.
    $int <<= $intOrNull; // error: Null value involved in <<= assignment on the right side.
    $int >>= $intOrNull; // error: Null value involved in >>= assignment on the right side.
    $string .= $stringOrNull; // error: Null value involved in .= assignment on the right side.
    $intOrNull ??= $stringOrNull;
}

function testOk(
    int $int,
    int $anotherInt,
    string $string,
    string $anotherString,
) {
    $int += $anotherInt;
    $int -= $anotherInt;
    $int *= $anotherInt;
    $int /= $anotherInt;
    $int %= $anotherInt;
    $int **= $anotherInt;
    $int &= $anotherInt;
    $int |= $anotherInt;
    $int ^= $anotherInt;
    $int <<= $anotherInt;
    $int >>= $anotherInt;
    $string .= $anotherString;
    $intOrNull ??= $string;
}
