<?php

namespace EnforceReadonlyPublicPropertyRule;

trait MyTrait {

    public ?string $public; // error: Public property `public` not marked as readonly.

    public readonly string $publicReadonly;

    protected string $protected;

    private string $private;

}

class MyClass {

    use MyTrait;

    public ?int $foo; // error: Public property `foo` not marked as readonly.

    public readonly int $bar;

    protected int $baz;

    private int $bag;

}

readonly class MyReadonlyClass {

    public ?int $foo;

    public readonly int $bar;

    protected int $baz;

    private int $bag;

}

