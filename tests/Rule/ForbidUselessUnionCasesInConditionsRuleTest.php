<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUselessUnionCasesInConditionsRule>
 */
class ForbidUselessUnionCasesInConditionsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUselessUnionCasesInConditionsRule(self::getContainer()->getByType(Standard::class));
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUselessUnionCasesInConditionsRule/code.php');
    }

}
