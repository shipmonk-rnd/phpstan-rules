<?php

namespace UnknownSymbol;

class A {
    public function aMethod(): bool {
        return true;
    }
}

function doFoo(B $b) {
    /** @var A $a */
    $a = createMagically();
    $a->aMethod();
    $b->bMethod();
}

