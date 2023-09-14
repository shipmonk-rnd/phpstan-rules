<?php declare(strict_types = 1);

namespace ImmediatelyCalledCallableThrowTypeExtension;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

interface ImmediateInterface {

    public function inheritedMethod(callable $first, callable $second): int;

}

class BaseImmediate implements ImmediateInterface {

    public function inheritedMethod(callable $first, callable $second): int {
        $first();
        $second();
        return 1;
    }

}

class Immediate extends BaseImmediate {

    public static function method(callable $callable): int {
        $callable();
        return 1;
    }

}

class MethodCallExtensionTest
{

    public function noThrow(): void
    {
    }

    /** @throws \Exception */
    public function throw(): void
    {
        throw new \Exception();
    }

    /** @throws \Exception */
    public static function staticThrow(): void
    {
        throw new \Exception();
    }

    public function testNoThrow(): void
    {
        try {
            $result = Immediate::method('ucfirst');
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

    public function testClosure(): void
    {
        try {
            $result = Immediate::method(static function (): void {
                throw new \Exception();
            });
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testClosureWithoutThrow(): void
    {
        try {
            $result = Immediate::method(static function (): void {
                return;
            });
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

    public function testFirstClassCallable(): void
    {
        try {
            $result = Immediate::method($this->throw(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testStaticFirstClassCallable(): void
    {
        try {
            $result = Immediate::method(static::staticThrow(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testFirstClassCallableNoThrow(): void
    {
        try {
            $result = Immediate::method($this->noThrow(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

    public function testInheritedMethod(): void
    {
        try {
            $result1 = (new Immediate())->inheritedMethod($this->noThrow(...), $this->noThrow(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result1);
        }

        try {
            $result2 = (new Immediate())->inheritedMethod($this->throw(...), $this->noThrow(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result2);
        }

        try {
            $result3 = (new Immediate())->inheritedMethod($this->noThrow(...), $this->throw(...));
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result3);
        }
    }

}


class FunctionCallExtensionTest
{

    public function noThrow(): void
    {
    }

    /** @throws \Exception */
    public function throw(): void
    {
        throw new \Exception();
    }

    /** @throws \Exception */
    public static function staticThrow(): void
    {
        throw new \Exception();
    }

    public function testNoThrow(): void
    {
        try {
            $result = array_map('ucfirst', []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

    public function testClosure(): void
    {
        try {
            $result = array_map(static function (): void {
                throw new \Exception();
            }, []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testClosureWithoutThrow(): void
    {
        try {
            $result = array_map(static function (): void {
                return;
            }, []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

    public function testFirstClassCallable(): void
    {
        try {
            $result = array_map($this->throw(...), []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testStaticFirstClassCallable(): void
    {
        try {
            $result = array_map(static::staticThrow(...), []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
        }
    }

    public function testFirstClassCallableNoThrow(): void
    {
        try {
            $result = array_map($this->noThrow(...), []);
        } finally {
            assertVariableCertainty(TrinaryLogic::createYes(), $result);
        }
    }

}
