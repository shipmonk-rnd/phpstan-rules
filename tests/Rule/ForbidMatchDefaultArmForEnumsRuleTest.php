<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidMatchDefaultArmForEnumsRule>
 */
class ForbidMatchDefaultArmForEnumsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidMatchDefaultArmForEnumsRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidMatchDefaultArmForEnumsRule/code.php');
    }

}
