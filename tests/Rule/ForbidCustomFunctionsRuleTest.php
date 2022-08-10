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
            ],
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCustomFunctionsRule/code.php');
    }

}
