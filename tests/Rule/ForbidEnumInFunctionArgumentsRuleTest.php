<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidEnumInFunctionArgumentsRule>
 */
class ForbidEnumInFunctionArgumentsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidEnumInFunctionArgumentsRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidEnumInFunctionArgumentsRule/code.php');
    }

}
