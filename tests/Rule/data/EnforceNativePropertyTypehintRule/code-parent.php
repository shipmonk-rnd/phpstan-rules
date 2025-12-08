<?php declare(strict_types = 1);

namespace EnforceNativePropertyTypehintRuleParent;

class ParentClass {

    /** @var int */
    public $typedInParent; // error: Missing native property typehint int

    public int $alreadyTyped;

    public $untypedInParent;

    /** @var string */
    public $newPropertyInParent; // error: Missing native property typehint string

}

class ChildClass extends ParentClass {

    // Should NOT report - property from parent (LSP)
    /** @var int */
    public $typedInParent;

    // Should NOT report - parent already has type
    public int $alreadyTyped;

    // Should NOT report - property from parent (LSP)
    /** @var string */
    public $untypedInParent;

    // Should report - new property in child
    /** @var bool */
    public $newProperty; // error: Missing native property typehint bool

}

class GrandchildClass extends ChildClass {

    // Should NOT report - property from parent hierarchy (LSP)
    /** @var int */
    public $typedInParent;

    // Should NOT report - property from parent hierarchy (LSP)
    /** @var bool */
    public $newProperty;

    // Should report - new property in grandchild
    /** @var float */
    public $grandchildProperty; // error: Missing native property typehint float

}
