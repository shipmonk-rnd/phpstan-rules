<?php declare(strict_types = 1);

namespace Rule;

use LogicException;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\Rule\ForbidUnsafeArrayKeyRule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUnsafeArrayKeyRule>
 */
class ForbidUnsafeArrayKeyRuleTest extends RuleTestCase
{

    private ?bool $checkMixed = null;

    protected function getRule(): Rule
    {
        if ($this->checkMixed === null) {
            throw new LogicException('Property checkMixed must be set');
        }

        return new ForbidUnsafeArrayKeyRule(
            $this->checkMixed,
        );
    }

    public function testMixedDisabled(): void
    {
        $this->checkMixed = false;
        $this->analyseFile(__DIR__ . '/data/ForbidUnsafeArrayKeyRule/mixed-disabled.php');
    }

    public function testMixedEnabled(): void
    {
        $this->checkMixed = true;
        $this->analyseFile(__DIR__ . '/data/ForbidUnsafeArrayKeyRule/mixed-enabled.php');
    }

}
