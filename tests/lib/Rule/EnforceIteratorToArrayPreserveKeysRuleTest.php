<?php declare(strict_types = 1);

namespace Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\Rule\EnforceIteratorToArrayPreserveKeysRule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<EnforceIteratorToArrayPreserveKeysRule>
 */
class EnforceIteratorToArrayPreserveKeysRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceIteratorToArrayPreserveKeysRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/EnforceIteratorToArrayPreserveKeysRule/code.php');
    }

}
