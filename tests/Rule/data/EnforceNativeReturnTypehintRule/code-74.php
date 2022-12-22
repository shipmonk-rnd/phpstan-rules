<?php declare(strict_types = 1);

namespace EnforceNativeReturnTypehintRule74;

class A {}
class B {}
interface I {}
interface J {}

class MyClass {

    /** @return list<string> */
    public function doNotReportWithTypehint1(): array {}

    /** @return int */
    public function doNotReportWithTypehint2(): never {}

    /** @return int */
    public function doNotReportWithTypehint3(): mixed {}

    /** @return float */
    public function doNotReportWithTypehint4(): int {}

    /** @return list<string> */
    public function requireArray() {} // error: Missing native return typehint array

    /** @return array<int, string> */
    public function requireArray2() {} // error: Missing native return typehint array

    /** @return array{id: int} */
    public function requireArray3() {} // error: Missing native return typehint array

    /** @return string[] */
    public function requireArray4() {} // error: Missing native return typehint array

    /** @return array */
    public function requireArray5() {} // error: Missing native return typehint array

    /** @return \Closure(): int */
    public function requireClosureCallable() {} // error: Missing native return typehint callable

    /** @return iterable */
    public function requireIterable() {} // error: Missing native return typehint iterable

    /** @return callable(int): string */
    public function requireCallable() {} // error: Missing native return typehint callable

    /** @return string|int */
    public function requireUnionOfScalars() {} // require PHP 8.0

    /** @return string|int|null */
    public function requireUnionOfScalarsWithNull() {} // require PHP 8.0

    /** @return I&J&A */
    public function requireIntersection() {} // require PHP 8.1

    /** @return A|B|int */
    public function requireMixedUnion1() {} // require PHP 8.0

    /** @return A|string|null */
    public function requireMixedUnion2() {} // require PHP 8.0

    /** @return mixed */
    public function requireMixed() {} // error: Missing native return typehint mixed

    /** @return mixed|int|string */
    public function requireMixed2() {} // error: Missing native return typehint mixed

    /** @return unknown-type */
    public function requireMixed3() {} // error: Missing native return typehint mixed

    /** @return mixed|int|null */
    public function requireMixed4() {} // error: Missing native return typehint mixed

    /** @return void */
    public function requireVoid() {} // error: Missing native return typehint void

    /** @return null */
    public function requireNullVoid() {} // cannot determine void vs return null

    /** @return never */
    public function requireNever() {} // error: Missing native return typehint never

    /** @return class-string */
    public function requireString() {} // error: Missing native return typehint string

    /** @return class-string|null */
    public function requireNullableString1() {} // error: Missing native return typehint ?string

    /** @return ?string */
    public function requireNullableString2() {} // error: Missing native return typehint ?string

    /** @return (A|B)&I */
    public function requireDNF() {} // possible in PHP 8.2

    /** @return true|null */
    public function requireTrueOrNull() {} // error: Missing native return typehint ?bool

}
