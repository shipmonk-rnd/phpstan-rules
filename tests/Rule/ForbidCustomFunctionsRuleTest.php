<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidCustomFunctionsRule>
 */
class ForbidCustomFunctionsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidCustomFunctionsRule(
            [
                'sleep' => 'Description 0',
                'ForbidCustomFunctionsRule\forbidden_namespaced_function' => 'Description 1',
                'ForbidCustomFunctionsRule\ClassWithForbiddenAllMethods::*' => 'Description 2',
                'ForbidCustomFunctionsRule\ClassWithForbiddenConstructor::__construct' => 'Description 3',
                'ForbidCustomFunctionsRule\SomeClass::forbiddenMethod' => 'Description 4',
                'ForbidCustomFunctionsRule\SomeClass::forbiddenStaticMethod' => 'Description 5',
                'ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceMethod' => 'Description 6',
                'ForbidCustomFunctionsRule\SomeInterface::forbiddenInterfaceStaticMethod' => 'Description 7',
                'ForbidCustomFunctionsRule\SomeParent::forbiddenMethodOfParent' => 'Description 8',
            ],
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCustomFunctionsRule/code.php');
    }

    public function testInvalidConfig1(): void
    {
        self::expectExceptionMessage("Unexpected forbidden function name, string expected, got 0. Usage: ['var_dump' => 'Remove debug code!'].");
        new ForbidCustomFunctionsRule(['var_dump'], $this->createMock(ReflectionProvider::class));
    }

    public function testInvalidConfig2(): void
    {
        self::expectExceptionMessage("Unexpected forbidden function description, string expected, got array. Usage: ['var_dump' => 'Remove debug code!'].");
        new ForbidCustomFunctionsRule(['var_dump' => []], $this->createMock(ReflectionProvider::class));
    }

    public function testInvalidConfig3(): void
    {
        self::expectExceptionMessage('Unexpected format of forbidden function Class::method::12, expected Namespace\Class::methodName');
        new ForbidCustomFunctionsRule(['Class::method::12' => 'Description'], $this->createMock(ReflectionProvider::class));
    }

}
