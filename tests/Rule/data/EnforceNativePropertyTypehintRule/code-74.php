<?php declare(strict_types = 1);

namespace EnforceNativePropertyTypehintRule74;

class A {}

class BasicTypes {

    /** @var int */
    public $shouldHaveInt; // error: Missing native property typehint int

    /** @var string */
    public $shouldHaveString; // error: Missing native property typehint string

    /** @var bool */
    public $shouldHaveBool; // error: Missing native property typehint bool

    /** @var float */
    public $shouldHaveFloat; // error: Missing native property typehint float

    /** @var array */
    public $shouldHaveArray; // error: Missing native property typehint array

    /** @var list<string> */
    public $shouldHaveArray2; // no error - list is not representable as array in native type

    /** @var array<int, string> */
    public $shouldHaveArray3; // error: Missing native property typehint array

    /** @var array{id: int} */
    public $shouldHaveArray4; // error: Missing native property typehint array

    /** @var object */
    public $shouldHaveObject; // error: Missing native property typehint object

    /** @var iterable */
    public $shouldHaveIterable; // error: Missing native property typehint iterable

    /** @var self */
    public $shouldHaveSelf; // error: Missing native property typehint self

    /** @var A */
    public $shouldHaveClass; // error: Missing native property typehint \EnforceNativePropertyTypehintRule74\A

    /** @var \stdClass */
    public $shouldHaveStdClass; // error: Missing native property typehint \stdClass

    /** @var ?int */
    public $shouldHaveNullableInt; // error: Missing native property typehint ?int

    /** @var int|null */
    public $shouldHaveNullableInt2; // error: Missing native property typehint ?int

    /** @var ?string */
    public $shouldHaveNullableString; // error: Missing native property typehint ?string

    /** @var ?A */
    public $shouldHaveNullableClass; // error: Missing native property typehint ?\EnforceNativePropertyTypehintRule74\A

    /** @var class-string */
    public $shouldHaveString2; // error: Missing native property typehint string

    // Should NOT report - already has type
    public int $hasType;

    // Should NOT report - no @var annotation
    public $noPhpDoc;

    // Should NOT report - union type not available in 7.4
    /** @var string|int */
    public $unionType;

    // Should NOT report - mixed not available in 7.4
    /** @var mixed */
    public $mixedType;

    // Should NOT report - null standalone not available in 7.4
    /** @var null */
    public $nullType;

    // Should NOT report - callable not allowed for properties
    /** @var callable */
    public $callableType;

}

trait MyTrait {
    // Should NOT report - in trait
    /** @var int */
    public $traitProperty;
}
