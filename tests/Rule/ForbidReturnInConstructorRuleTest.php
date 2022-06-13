<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidReturnInConstructorRule>
 */
class ForbidReturnInConstructorRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidReturnInConstructorRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidReturnInConstructorRule/code.php');
    }

}
