<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidNullInAssignOperationsRule>
 */
class ForbidNullInAssignOperationsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidNullInAssignOperationsRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNullInAssignOperatorsRule/code.php');
    }

}
