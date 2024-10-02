<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidFetchOnMixedRule>
 */
class ForbidFetchOnMixedRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidFetchOnMixedRule(
            self::getContainer()->getByType(Printer::class), // @phpstan-ignore phpstanApi.classConstant
            (bool) self::getContainer()->getParameter('checkExplicitMixed'),
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidFetchOnMixedRuleTest/code.php');
    }

}
