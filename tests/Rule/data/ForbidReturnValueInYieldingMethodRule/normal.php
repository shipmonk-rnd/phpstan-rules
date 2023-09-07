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
