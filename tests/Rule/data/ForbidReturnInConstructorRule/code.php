<?php declare(strict_types = 1);

class WithReturn {

    public function __construct()
    {
        return; // error: Using return statement in constructor is forbidden to be able to check useless default values. Either create static constructors of use if-else.
    }
}

class WithoutReturn extends WithReturn {

    public function __construct()
    {
        parent::__construct();
    }

    public function anyMethod(): void
    {
        return;
    }
}

trait MyTrait {

    public function __construct() {
        return; // error: Using return statement in constructor is forbidden to be able to check useless default values. Either create static constructors of use if-else.
    }

}

class WithTraitUse {

    use MyTrait;

}
