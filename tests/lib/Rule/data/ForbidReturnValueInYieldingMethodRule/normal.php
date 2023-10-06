<?php declare(strict_types = 1);

namespace ForbidReturnValueInYieldingMethodRule;

class Normal {

    public function returnMixed() {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, but this method is not marked to return Generator.
    }

    public function returnIterable(): iterable {
        yield 1;
        return 2; // error: Returned value from yielding method can be accessed only via Generator::getReturn, but this method is not marked to return Generator.
    }

    public function returnGenerator(): \Generator {
        yield 1;
        return 2;
    }

    /**
     * @return iterable&\Generator
     */
    public function returnGeneratorAndIterable() {
        yield 1;
        return 2;
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
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, but this method is not marked to return Generator.
}

function returnGenerator(): \Generator {
    yield 1;
    return 2;
}

function (): iterable {
    yield 1;
    return 2; // error: Returned value from yielding function can be accessed only via Generator::getReturn, but this method is not marked to return Generator.
};

function (): \Generator {
    yield 1;
    return 2;
};
