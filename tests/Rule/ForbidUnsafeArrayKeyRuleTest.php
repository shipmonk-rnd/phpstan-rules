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

    private ?bool $checkInsideIsset = null;

    protected function getRule(): Rule
    {
        if ($this->checkMixed === null) {
            throw new LogicException('Property checkMixed must be set');
        }

        if ($this->checkInsideIsset === null) {
            throw new LogicException('Property checkInsideIsset must be set');
        }

        return new ForbidUnsafeArrayKeyRule(
            $this->checkMixed,
            $this->checkInsideIsset,
        );
    }

    public function testStrict(): void
    {
        $this->checkMixed = true;
        $this->checkInsideIsset = true;
        $this->analyseFile(__DIR__ . '/data/ForbidUnsafeArrayKeyRule/default.php');
    }

    public function testLessStrict(): void
    {
        $this->checkMixed = false;
        $this->checkInsideIsset = false;
        $this->analyseFile(__DIR__ . '/data/ForbidUnsafeArrayKeyRule/less-strict.php');
    }

}
