<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbidProtectedEnumMethodRule>
 */
class ForbidProtectedEnumMethodRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidProtectedEnumMethodRule();
    }

    public function test(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->analyseFile(__DIR__ . '/data/ForbidProtectedEnumMethodRule/code.php');
    }

}
