<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidTrickyComparisonRule>
 */
class ForbidTrickyComparisonRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidTrickyComparisonRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidTrickyComparisonRule/code.php');
    }

}
