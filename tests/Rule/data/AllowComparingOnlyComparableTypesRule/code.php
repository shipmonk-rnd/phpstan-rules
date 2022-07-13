<?php declare(strict_types = 1);

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
    float $float,
    bool $bool,
) {
    $foos > $foo; // error: Comparison array > Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $nullableInt > $int; // error: Comparison int|null > int contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    null > $int; // error: Comparison null > int contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $foo2; // error: Comparison Foo > Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $fooOrBar; // error: Comparison Foo > Bar|Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $foo > $fooAndBar; // error: Comparison Foo > Bar&Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $string > 'foo';
    $int > 2;
    $float > 2;
    $bool > true; // error: Comparison bool > true contains non-comparable type, only int|float|string|DateTimeInterface is allowed.
    $dateTime > $dateTimeImmutable;
    $dateTime > $foo; // error: Comparison DateTime > Foo contains non-comparable type, only int|float|string|DateTimeInterface is allowed.

    $string > $int; // error: Cannot compare different types in string > int.
    $float > $int;
    $dateTime > $string; // error: Cannot compare different types in DateTime > string.
};
