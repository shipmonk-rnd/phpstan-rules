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
        /** @var class-string $superclass */
        $superclass = 'ClassSuffixNamingRule\CheckedParent'; // @phpstan-ignore-line
        return new ClassSuffixNamingRule([$superclass => 'Suffix']);
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ClassSuffixNamingRule/code.php');
    }

}
