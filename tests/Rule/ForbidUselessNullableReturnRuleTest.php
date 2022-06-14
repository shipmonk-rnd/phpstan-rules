<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUselessNullableReturnRule>
 */
class ForbidUselessNullableReturnRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUselessNullableReturnRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUselessNullableReturnRule/code.php');
    }

}
