<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<RequirePreviousExceptionPassRule>
 */
class RequirePreviousExceptionPassRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new RequirePreviousExceptionPassRule(self::getContainer()->getByType(Standard::class));
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/RequirePreviousExceptionPassRule/code.php');
    }

}
