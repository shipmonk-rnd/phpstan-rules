<?php

namespace ForbidNullInBinaryOperationsRule;

function test(
    bool $bool,
    ?bool $nullableBool,
    string $string,
    ?string $nullableString,
    int $int,
    ?int $nullableInt
) {
    $int & $nullableInt; // error: Null value involved in binary operation: int & int|null
    $int | $nullableInt; // error: Null value involved in binary operation: int | int|null
    $int ** $nullableInt; // error: Null value involved in binary operation: int ** int|null
    $int << $nullableInt; // error: Null value involved in binary operation: int << int|null
    $int >> $nullableInt; // error: Null value involved in binary operation: int >> int|null
    $int != $nullableInt; // error: Null value involved in binary operation: int != int|null
    $int == $nullableInt; // error: Null value involved in binary operation: int == int|null
    $int ?? $nullableInt;
    $int === $nullableInt;
    $int !== $nullableInt;
    $int <=> $nullableInt;
    $int <= $nullableInt;
    $int >= $nullableInt;
    $int < $nullableInt;
    $int > $nullableInt;
    $int + $nullableInt; // error: Null value involved in binary operation: int + int|null
    $bool and $nullableBool; // error: Null value involved in binary operation: bool and bool|null
    $bool or $nullableBool; // error: Null value involved in binary operation: bool or bool|null
    $bool xor $nullableBool; // error: Null value involved in binary operation: bool xor bool|null
    $bool && $nullableBool; // error: Null value involved in binary operation: bool && bool|null
    $bool || $nullableBool; // error: Null value involved in binary operation: bool || bool|null
    $int % $nullableInt; // error: Null value involved in binary operation: int % int|null
    $int - $nullableInt; // error: Null value involved in binary operation: int - int|null
    $int / $nullableInt; // error: Null value involved in binary operation: int / int|null
    $int * $nullableInt; // error: Null value involved in binary operation: int * int|null
    $int ^ $nullableInt; // error: Null value involved in binary operation: int ^ int|null
    $string . $nullableString; // error: Null value involved in binary operation: string . string|null
}
