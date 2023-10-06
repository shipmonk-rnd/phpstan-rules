<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUnsetClassFieldRule>
 */
class ForbidUnsetClassFieldRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUnsetClassFieldRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUnsetClassFieldRule/code.php');
    }

}
