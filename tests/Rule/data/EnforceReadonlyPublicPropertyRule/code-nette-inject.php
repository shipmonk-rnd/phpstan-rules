<?php

namespace EnforceReadonlyPublicPropertyRule84;

use Nette\DI\Attributes\Inject;

class MyPresenterClass {

    #[Inject]
    public int $foo;

    /** @inject */
    public int $foo2;

    public int $foo3; // error: Public property `foo3` not marked as readonly.

    /**
     * @int
     * @inject with comment
     * @deprecated
     */
    public int $foo4;
}
