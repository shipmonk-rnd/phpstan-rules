<?php declare(strict_types = 1);

namespace EnforceNativeReturnTypehintRule81;

class A {}
class B {}
interface I {}
interface J {}

class DeductFromPhpDocs {

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
    public function requireClosureCallable() {} // error: Missing native return typehint Closure

    /** @return iterable */
    public function requireIterable() {} // error: Missing native return typehint iterable

    /** @return callable(int): string */
    public function requireCallable() {} // error: Missing native return typehint callable

    /** @return string|int */
    public function requireUnionOfScalars() {} // error: Missing native return typehint string|int

    /** @return string|int|null */
    public function requireUnionOfScalarsWithNull() {} // error: Missing native return typehint string|int|null

    /** @return I&J&A */
    public function requireIntersection() {} // error: Missing native return typehint \EnforceNativeReturnTypehintRule81\I&\EnforceNativeReturnTypehintRule81\J&\EnforceNativeReturnTypehintRule81\A

    /** @return A|B|int */
    public function requireMixedUnion1() {} // error: Missing native return typehint \EnforceNativeReturnTypehintRule81\A|\EnforceNativeReturnTypehintRule81\B|int

    /** @return A|string|null */
    public function requireMixedUnion2() {} // error: Missing native return typehint \EnforceNativeReturnTypehintRule81\A|string|null

    /** @return A|null */
    public function requireUnionWithNullOnly() {} // error: Missing native return typehint ?\EnforceNativeReturnTypehintRule81\A

    /** @return mixed */
    public function requireMixed() {}

    /** @return mixed|int|string */
    public function requireMixed2() {}

    /** @return unknown-type */
    public function requireMixed3() {}

    /** @return mixed|int|null */
    public function requireMixed4() {}

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

class DeductFromReturnStatements {

    public function __construct()
    {
        function () { // error: Missing native return typehint string
            return '';
        };
    }

    public function __clone()
    {

    }

    public function __destruct()
    {

    }

    public function requireUnionOfScalars(bool $bool) // error: Missing native return typehint string|int
    {
        if ($bool) {
            return '';
        }
        return 1;
    }

    public function requireVoid() // error: Missing native return typehint void
    {
    }

    public function returnNewSelf() // error: Missing native return typehint self
    {
        return new self;
    }

    public function returnThis() // error: Missing native return typehint static
    {
        return $this;
    }

    /**
     * @return static
     */
    public function returnStatic() // error: Missing native return typehint static
    {
        return $this;
    }

    public function returnNull()
    {
        return null;
    }

    public function requireGenerator() // error: Missing native return typehint Generator
    {
        yield 1;
        return 2;
    }

    public function requireInt() // error: Missing native return typehint int
    {
        return 1;
    }

    public function requireString() // error: Missing native return typehint string
    {
        return self::class;
    }

    public function testClosureWithoutReturn(): \Closure
    {
        function () { // error: Missing native return typehint static
            return $this;
        };

        return function () { // error: Missing native return typehint int
            return 1;
        };
    }

}

/** @return int */
function functionWithPhpDoc() { // error: Missing native return typehint int

};


function functionWithReturn() { // error: Missing native return typehint int
    return 1;
};

trait TraitWithReturnSelf {

    abstract protected function returnException(): \Throwable;

    /**
     * @return \Throwable
     */
    public function returnDiffersPerUser1()
    {
        return static::returnException();
    }

    public function returnDiffersPerUser2()
    {
        return static::returnException();
    }

    public function returnSelf1()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function returnSelf2()
    {
        return $this;
    }

}

class TraitUser1 {
    use TraitWithReturnSelf;

    protected function returnException(): \RuntimeException
    {
        return new \RuntimeException();
    }
}

class TraitUser2 {
    use TraitWithReturnSelf;

    protected function returnException(): \LogicException
    {
        return new \LogicException();
    }
}
