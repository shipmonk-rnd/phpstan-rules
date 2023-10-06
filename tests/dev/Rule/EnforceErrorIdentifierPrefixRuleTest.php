<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<EnforceErrorIdentifierPrefixRule>
 */
class EnforceErrorIdentifierPrefixRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceErrorIdentifierPrefixRule();
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/EnforceErrorIdentifierPrefixRule/code.php');
    }

}
