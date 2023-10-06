<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<AllowComparingOnlyComparableTypesRule>
 */
class AllowComparingOnlyComparableTypesRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new AllowComparingOnlyComparableTypesRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/AllowComparingOnlyComparableTypesRule/code.php');
    }

}
