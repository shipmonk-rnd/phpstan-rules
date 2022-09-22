<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnforceReadonlyPublicPropertyRule>
 */
class EnforceReadonlyPublicPropertyRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceReadonlyPublicPropertyRule();
    }

    public function test(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code.php');
    }

}
