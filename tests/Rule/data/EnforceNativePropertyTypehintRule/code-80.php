<?php declare(strict_types = 1);

namespace EnforceNativePropertyTypehintRule80;

class A {}
class B {}

class UnionAndMixedTypes {

    /** @var mixed */
    public $shouldHaveMixed; // error: Missing native property typehint mixed

    /** @var string|int */
    public $shouldHaveUnion; // error: Missing native property typehint string|int

    /** @var string|int|null */
    public $shouldHaveNullableUnion; // error: Missing native property typehint string|int|null

    /** @var A|B */
    public $shouldHaveObjectUnion; // error: Missing native property typehint \EnforceNativePropertyTypehintRule80\A|\EnforceNativePropertyTypehintRule80\B

    /** @var A|B|null */
    public $shouldHaveNullableObjectUnion; // error: Missing native property typehint \EnforceNativePropertyTypehintRule80\A|\EnforceNativePropertyTypehintRule80\B|null

    /** @var A|string */
    public $shouldHaveMixedUnion; // error: Missing native property typehint \EnforceNativePropertyTypehintRule80\A|string

    /** @var A|string|null */
    public $shouldHaveNullableMixedUnion; // error: Missing native property typehint \EnforceNativePropertyTypehintRule80\A|string|null

    /** @var static */
    public $shouldHaveSelf; // error: Missing native property typehint self

    // Should NOT report - intersection not available in 8.0
    /** @var \Countable&\Iterator */
    public $intersectionType;

    // Should NOT report - null standalone not available in 8.0
    /** @var null */
    public $nullType;

    // Should report bool - true/false standalone not available in 8.0
    /** @var true */
    public $trueType; // error: Missing native property typehint bool

    /** @var false */
    public $falseType; // error: Missing native property typehint bool

    // Should NOT report - callable not allowed for properties
    /** @var callable */
    public $callableType;

    // Should report - basic types still work
    /** @var int */
    public $shouldHaveInt; // error: Missing native property typehint int

}
