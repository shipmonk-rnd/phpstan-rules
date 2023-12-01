<?php declare(strict_types = 1);

namespace AllowComparingOnlyComparableTypesRule;

use DateTime;
use DateTimeImmutable;

interface Foo {}
interface Bar {}

/**
 * @param Foo[] $foos
 */
$fn = function (
    array $foos,
    Foo $foo,
    Foo $foo2,
    Foo|Bar $fooOrBar,
    Foo&Bar $fooAndBar,
    DateTime $dateTime,
    DateTimeImmutable $dateTimeImmutable,
    string $string,
    int $int,
    ?int $nullableInt,
    int|float $intOrFloat,
    float $float,
    bool $bool,
    mixed $mixed,
) {
    $foos > $foo; // error: Comparison array > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $nullableInt > $int; // error: Comparison int|null > int contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    null > $int; // error: Comparison null > int contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $foo > $foo2; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $foo > $fooOrBar; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Bar|AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $foo > $fooAndBar; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Bar&AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $string > 'foo';
    $int > 2;
    $float > 2;
    $int > $intOrFloat;
    $string > $intOrFloat; // error: Cannot compare different types in string > float|int.
    $bool > true; // error: Comparison bool > true contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    $dateTime > $dateTimeImmutable;
    $dateTime > $foo; // error: Comparison DateTime > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.

    $string > $int; // error: Cannot compare different types in string > int.
    $float > $int;
    $dateTime > $string; // error: Cannot compare different types in DateTime > string.

    [$int, $string] > [$int, $string];
    [[$int]] > [[$int]];
    [$int, $float, $intOrFloat, $intOrFloat] > [$int, $int, $int, $float];
    [$int, $string] > $foos; // error: Comparison array{int, string} > array contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    [$int] > [$int, $int]; // error: Cannot compare different types in array{int} > array{int, int}.
    [$int, $string] > [$int]; // error: Cannot compare different types in array{int, string} > array{int}.
    [$string, $int] > [$int, $string]; // error: Cannot compare different types in array{string, int} > array{int, string}.
    [$foo] > [$foo]; // error: Comparison array{AllowComparingOnlyComparableTypesRule\Foo} > array{AllowComparingOnlyComparableTypesRule\Foo} contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    [$int] > [$mixed]; // error: Comparison array{int} > array{mixed} contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    [$dateTime] > [[$dateTimeImmutable]]; // error: Cannot compare different types in array{DateTime} > array{array{DateTimeImmutable}}.

    [0 => $int, 1 => $string] > [$int, $string];
    [1 => $int, 0 => $string] > [$int, $string]; // error: Comparison array{1: int, 0: string} > array{int, string} contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
    ['X' => $int, 'Y' => $string] > ['X' => $int, 'Y' => $string]; // error: Comparison array{X: int, Y: string} > array{X: int, Y: string} contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.
};
