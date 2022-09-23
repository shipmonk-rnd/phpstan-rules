<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidAssignmentNotMatchingVarDocRule>
 */
class ForbidAssignmentNotMatchingVarDocRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidAssignmentNotMatchingVarDocRule(self::getContainer()->getByType(FileTypeMapper::class));
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidAssignmentNotMatchingVarDocRule/code.php');
    }

}
