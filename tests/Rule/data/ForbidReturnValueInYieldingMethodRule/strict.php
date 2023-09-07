<?php declare(strict_types = 1);

namespace Rule\data\ForbidReturnValueInYieldingMethodRule;

class Strict {

    public function returnMixed() {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, this approach is denied.
    }

    public function returnIterable(): iterable {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, this approach is denied.
    }

    public function returnGenerator(): \Generator {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, this approach is denied.
    }

    /**
     * @return iterable&\Generator
     */
    public function returnGeneratorAndIterable() {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, this approach is denied.
    }

}

class NoYield {
    public function returnGeneratorNoYield(): \Generator {
        return $this->returnGeneratorByYield();
    }

    public function returnGeneratorByYield(): \Generator {
        yield 1;
    }
}

class NoValue {
    public function returnMixed() {
        yield 1;
        return;
    }

    public function returnIterable(): iterable {
        yield 1;
        return;
    }

    public function returnGenerator(): \Generator {
        yield 1;
        return;
    }
}


function returnIterable(): iterable {
    yield 1;
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, this approach is denied.
}

function returnGenerator(): \Generator {
    yield 1;
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, this approach is denied.
}

function (): iterable {
    yield 1;
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, this approach is denied.
};

function (): \Generator {
    yield 1;
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, this approach is denied.
};

