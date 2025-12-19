<?php declare(strict_types = 1);

namespace EnforceNativePropertyTypehintRule82;

interface I {}
interface J {}
class A implements I {}
class B implements I {}

class DnfAndStandaloneTypes {

    /** @var null */
    public $shouldHaveNull; // error: Missing native property typehint null

    /** @var true */
    public $shouldHaveTrue; // error: Missing native property typehint true

    /** @var false */
    public $shouldHaveFalse; // error: Missing native property typehint false

    /** @var (A&I)|B */
    public $shouldHaveDnf; // error: Missing native property typehint \EnforceNativePropertyTypehintRule82\A|\EnforceNativePropertyTypehintRule82\B

    /** @var (A&I)|null */
    public $shouldHaveNullableDnf; // error: Missing native property typehint \EnforceNativePropertyTypehintRule82\A|null

    /** @var true|null */
    public $shouldHaveNullableTrue; // error: Missing native property typehint true|null

    /** @var false|null */
    public $shouldHaveNullableFalse; // error: Missing native property typehint false|null

    // Basic types should still work
    /** @var int */
    public $shouldHaveInt; // error: Missing native property typehint int

    /** @var I&J */
    public $shouldHaveIntersection; // error: Missing native property typehint \EnforceNativePropertyTypehintRule82\I&\EnforceNativePropertyTypehintRule82\J

    /** @var string|int */
    public $shouldHaveUnion; // error: Missing native property typehint string|int

}
