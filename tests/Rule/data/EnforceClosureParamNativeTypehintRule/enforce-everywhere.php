<?php declare(strict_types = 1);

namespace ForbidImplicitMixedInClosureParamsRule\Everywhere;


/**
 * @param list<string> $a
 * @param list<mixed> $b
 */
function test($a, $b, $c, array $d): void
{
    array_map(function ($item) {}, [1]); // error: Missing parameter typehint for closure parameter $item.
    array_map(function ($item) {}, $a); // error: Missing parameter typehint for closure parameter $item.
    array_map(function ($item) {}, $b); // error: Missing parameter typehint for closure parameter $item.
    array_map(function ($item) {}, $c); // error: Missing parameter typehint for closure parameter $item.
    array_map(function ($item) {}, $d); // error: Missing parameter typehint for closure parameter $item.
    array_map(function (int $item) {}, $c);
    array_map(function (int $item) {}, $d);

    array_map(static fn($item) => 1, [1]); // error: Missing parameter typehint for arrow function parameter $item.
    array_map(static fn($item) => 1, $a); // error: Missing parameter typehint for arrow function parameter $item.
    array_map(static fn($item) => 1, $b); // error: Missing parameter typehint for arrow function parameter $item.
    array_map(static fn($item) => 1, $c); // error: Missing parameter typehint for arrow function parameter $item.
    array_map(static fn($item) => 1, $d); // error: Missing parameter typehint for arrow function parameter $item.
    array_map(static fn(int $item) => 1, $c);
    array_map(static fn(int $item) => 1, $d);

    function ($item2) {}; // error: Missing parameter typehint for closure parameter $item2.
    function (mixed $item2) {};
}
