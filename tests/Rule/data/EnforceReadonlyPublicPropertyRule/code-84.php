<?php

namespace EnforceReadonlyPublicPropertyRule84;

trait MyTrait {

    public ?string $public; // error: Public property `public` not marked as readonly.

    public ?string $hooked { get => $this->hooked; }

    public readonly string $publicReadonly;

    protected string $protected;

    private string $private;

    public static string $static;

}

class MyClass {

    use MyTrait;

    public ?int $foo; // error: Public property `foo` not marked as readonly.

    public ?string $classHooked { set => strtolower($value); }

    public readonly int $bar;

    protected int $baz;

    private int $bag;

    public static string $static;

}

readonly class MyReadonlyClass {

    public ?int $foo;

    public readonly int $bar;

    public ?string $hooked { get => $this->hooked; }

    protected int $baz;

    private int $bag;

}

interface MyInterface {
    public string $key { get; }
}

class ImplementingClass implements MyInterface {
    public string $key; // error: Public property `key` not marked as readonly.
}

class ImplementingClass2 implements MyInterface {
    public readonly string $key;
}

class AsymmetricVisibility {
    public private(set) string $privateSet;
    public protected(set) string $protectedSet;
    public public(set) string $publicSet; // error: Public property `publicSet` not marked as readonly.
}
