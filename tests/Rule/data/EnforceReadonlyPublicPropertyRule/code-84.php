<?php

namespace EnforceReadonlyPublicPropertyRule84;

trait MyTrait {

    public ?string $public; // error: Public property `public` not marked as readonly.

    public ?string $hooked { get => $this->hooked; }

    public readonly string $publicReadonly;

    protected string $protected;

    private string $private;

}

class MyClass {

    use MyTrait;

    public ?int $foo; // error: Public property `foo` not marked as readonly.

    public ?string $classHooked { set => strtolower($value); }

    public readonly int $bar;

    protected int $baz;

    private int $bag;

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
