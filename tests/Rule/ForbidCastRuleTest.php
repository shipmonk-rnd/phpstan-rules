<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidCastRule>
 */
class ForbidCastRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidCastRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCastRule/code.php');
    }

    public function testClassPhp85(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCastRule/code-85.php');
    }

}
