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
) {
    $foos > $foo; // error: Comparison array > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $nullableInt > $int; // error: Comparison int|null > int contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    null > $int; // error: Comparison null > int contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $foo2; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $fooOrBar; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Bar|AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $fooAndBar; // error: Comparison AllowComparingOnlyComparableTypesRule\Foo > AllowComparingOnlyComparableTypesRule\Bar&AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $string > 'foo';
    $int > 2;
    $float > 2;
    $int > $intOrFloat;
    $string > $intOrFloat; // error: Cannot compare different types in string > float|int.
    $bool > true; // error: Comparison bool > true contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $dateTime > $dateTimeImmutable;
    $dateTime > $foo; // error: Comparison DateTime > AllowComparingOnlyComparableTypesRule\Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.

    $string > $int; // error: Cannot compare different types in string > int.
    $float > $int;
    $dateTime > $string; // error: Cannot compare different types in DateTime > string.
};
