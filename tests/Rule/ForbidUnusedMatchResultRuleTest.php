<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<ForbidUnusedMatchResultRule>
 */
class ForbidUnusedMatchResultRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUnusedMatchResultRule();
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/ForbidUnusedMatchResultRule/unused-match-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUnusedMatchResultRule/code.php');
    }

}
