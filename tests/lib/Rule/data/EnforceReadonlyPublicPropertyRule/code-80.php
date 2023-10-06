<?php

namespace EnforceReadonlyPublicPropertyRule80;

trait MyTrait {

    public ?string $public;

    protected string $protected;

    private string $private;

}

class MyClass {

    use MyTrait;

    public ?int $foo;

    public int $bar;

    protected int $baz;

    private int $bag;

}


