<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidInvalidInlineVarPhpDocRule>
 */
class ForbidInvalidInlineVarPhpDocRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidInvalidInlineVarPhpDocRule(self::getContainer()->getByType(FileTypeMapper::class));
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidInvalidInlineVarPhpDocRule/code.php');
    }

}
