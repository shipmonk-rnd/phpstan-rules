<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidNullInBinaryOperationsRule>
 */
class ForbidNullInBinaryOperationsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidNullInBinaryOperationsRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNullInBinaryOperationsRule/code.php');
    }

}
