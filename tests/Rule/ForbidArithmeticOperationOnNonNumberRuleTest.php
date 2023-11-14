<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidArithmeticOperationOnNonNumberRule>
 */
class ForbidArithmeticOperationOnNonNumberRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidArithmeticOperationOnNonNumberRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/code.php');
    }

}
