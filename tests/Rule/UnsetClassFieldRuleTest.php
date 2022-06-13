<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<UnsetClassFieldRule>
 */
class UnsetClassFieldRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new UnsetClassFieldRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/UnsetClassFieldRule/code.php');
    }

}
