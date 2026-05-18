<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

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
        $this->analyseFile(
            __DIR__ . '/data/EnforceEnumMatchRule/code.php',
        );
    }

}
