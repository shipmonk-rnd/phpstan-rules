<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUnusedClosureParametersRule>
 */
class ForbidUnusedClosureParametersRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUnusedClosureParametersRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUnusedClosureParametersRule/code.php');
    }

}
