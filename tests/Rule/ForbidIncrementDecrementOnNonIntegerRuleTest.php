<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidIncrementDecrementOnNonIntegerRule>
 */
class ForbidIncrementDecrementOnNonIntegerRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidIncrementDecrementOnNonIntegerRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidIncrementDecrementOnNonIntegerRule/code.php');
    }

}
