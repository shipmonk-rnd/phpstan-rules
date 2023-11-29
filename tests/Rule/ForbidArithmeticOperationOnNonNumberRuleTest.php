<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidArithmeticOperationOnNonNumberRule>
 */
class ForbidArithmeticOperationOnNonNumberRuleTest extends RuleTestCase
{

    private ?bool $allowNumericString = null;

    protected function getRule(): Rule
    {
        if ($this->allowNumericString === null) {
            throw new LogicException('allowNumericString must be set');
        }

        return new ForbidArithmeticOperationOnNonNumberRule($this->allowNumericString);
    }

    public function test(): void
    {
        $this->allowNumericString = true;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/code.php');
    }

    public function testNoNumericString(): void
    {
        $this->allowNumericString = false;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/no-numeric-string.php');
    }

}
