<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnforceEnumMatchRule>
 */
class EnforceEnumMatchRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceEnumMatchRule();
    }

    public function testRule(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->analyseFile(
            __DIR__ . '/data/EnforceEnumMatchRule/code.php',
        );
    }

}
