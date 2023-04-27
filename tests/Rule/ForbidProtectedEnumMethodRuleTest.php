<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidProtectedEnumMethodRule>
 */
class ForbidProtectedEnumMethodRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidProtectedEnumMethodRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidProtectedEnumMethodRule/code.php');
    }

}
