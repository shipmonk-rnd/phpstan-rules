<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbidUselessNullableReturnRule>
 */
class ForbidUselessNullableReturnRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUselessNullableReturnRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUselessNullableReturnRule/code.php');
    }

    public function testPropertyHooks(): void
    {
        if (PHP_VERSION_ID < 8_00_00) {
            self::markTestSkipped('PHP7 parser fails with property hooks');
        }

        $this->analyseFile(__DIR__ . '/data/ForbidUselessNullableReturnRule/code-hook.php');
    }

}
