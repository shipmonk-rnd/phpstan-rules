<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ClassSuffixNamingRule>
 */
class ClassSuffixNamingRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ClassSuffixNamingRule(['ClassSuffixNamingRule\CheckedParent' => 'Suffix']); // @phpstan-ignore-line
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ClassSuffixNamingRule/code.php');
    }

}
