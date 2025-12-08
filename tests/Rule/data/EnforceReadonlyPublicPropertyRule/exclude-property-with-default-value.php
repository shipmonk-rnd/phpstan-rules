<?php

namespace EnforceReadonlyPublicPropertyRuleExcludePropertyWithDefaultValue;

trait MyTrait {

    public ?string $public; // error: Public property `public` not marked as readonly.

    public int $default = 42;

}

class MyClass {

    use MyTrait;

    public ?int $foo; // error: Public property `foo` not marked as readonly.

    public int $quux = 7;

}

