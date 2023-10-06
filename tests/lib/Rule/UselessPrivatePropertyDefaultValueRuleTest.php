<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<UselessPrivatePropertyDefaultValueRule>
 */
class UselessPrivatePropertyDefaultValueRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new UselessPrivatePropertyDefaultValueRule();
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/UselessPrivatePropertyDefaultValueRule/top-level-fetch-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/UselessPrivatePropertyDefaultValueRule/code.php');
    }

}
