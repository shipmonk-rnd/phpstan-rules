<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidMethodCallOnMixedRule>
 */
class ForbidMethodCallOnMixedRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidMethodCallOnMixedRule(new Standard());
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidMethodCallOnMixedRule/code.php');
    }

}
