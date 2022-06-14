<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<ForbidUnusedExceptionRule>
 */
class ForbidUnusedExceptionRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUnusedExceptionRule(new Standard());
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/ForbidUnusedExceptionRule/unused-exception-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUnusedExceptionRule/code.php');
    }

}
