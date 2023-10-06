<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

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
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->analyseFile(__DIR__ . '/data/ForbidEnumInFunctionArgumentsRule/code.php');
    }

}
