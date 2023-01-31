<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<EnforceListReturnRule>
 */
class EnforceListReturnRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceListReturnRule(true);
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/EnforceListReturnRule/code.php');
    }

}
