<?php declare(strict_types = 1);

namespace EnforceNativePropertyTypehintRule81;

interface I {}
interface J {}

class IntersectionTypes {

    /** @var I&J */
    public $shouldHaveIntersection; // error: Missing native property typehint \EnforceNativePropertyTypehintRule81\I&\EnforceNativePropertyTypehintRule81\J

    /** @var \Countable&\Iterator */
    public $shouldHaveBuiltinIntersection; // error: Missing native property typehint \Countable&\Iterator

    // Should NOT report - DNF not available in 8.1
    /** @var (I&J)|null */
    public $dnfType;

    // Should NOT report - null standalone not available in 8.1
    /** @var null */
    public $nullType;

    // Should report bool - true/false standalone not available in 8.1
    /** @var true */
    public $trueType; // error: Missing native property typehint bool

    // Basic types should still work
    /** @var int */
    public $shouldHaveInt; // error: Missing native property typehint int

    /** @var mixed */
    public $shouldHaveMixed; // error: Missing native property typehint mixed

    /** @var string|int */
    public $shouldHaveUnion; // error: Missing native property typehint string|int

}
