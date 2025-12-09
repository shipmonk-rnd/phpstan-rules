<?php

namespace EnforceReadonlyPublicPropertyRule81;

trait MyTrait {

    public ?string $public; // error: Public property `public` not marked as readonly.

    public readonly string $publicReadonly;

    protected string $protected;

    private string $private;

    public static string $static;

    public int $default = 42; // error: Public property `default` not marked as readonly.

    public $untyped;

}

class MyClass {

    use MyTrait;

    public ?int $foo; // error: Public property `foo` not marked as readonly.

    public readonly int $bar;

    protected int $baz;

    private int $bag;

    public static string $static;

    public int $quux = 7; // error: Public property `quux` not marked as readonly.

    public $quuz;

}

readonly class MyReadonlyClass {

    public ?int $foo;

    public readonly int $bar;

    protected int $baz;

    private int $bag;

}

