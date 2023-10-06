<?php declare(strict_types = 1);

namespace Rule;

use LogicException;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\Rule\ForbidReturnValueInYieldingMethodRule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidReturnValueInYieldingMethodRule>
 */
class ForbidReturnValueInYieldingMethodRuleTest extends RuleTestCase
{

    private ?bool $strictMode = null;

    protected function getRule(): Rule
    {
        if ($this->strictMode === null) {
            throw new LogicException('Strict mode must be set');
        }

        return new ForbidReturnValueInYieldingMethodRule($this->strictMode);
    }

    public function testNormalMode(): void
    {
        $this->strictMode = false;
        $this->analyseFile(__DIR__ . '/data/ForbidReturnValueInYieldingMethodRule/normal.php');
    }

    public function testStrictMode(): void
    {
        $this->strictMode = true;
        $this->analyseFile(__DIR__ . '/data/ForbidReturnValueInYieldingMethodRule/strict.php');
    }

}
