<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidVariableTypeOverwritingRule>
 */
class ForbidVariableTypeOverwritingRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidVariableTypeOverwritingRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidVariableTypeOverwritingRule/code.php');
    }

}
