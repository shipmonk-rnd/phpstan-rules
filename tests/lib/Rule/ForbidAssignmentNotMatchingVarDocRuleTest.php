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

    private ?bool $allowNarrowing = null;

    protected function getRule(): Rule
    {
        self::assertNotNull($this->allowNarrowing);

        return new ForbidAssignmentNotMatchingVarDocRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            $this->allowNarrowing,
        );
    }

    public function testDefault(): void
    {
        $this->allowNarrowing = false;
        $this->analyseFile(__DIR__ . '/data/ForbidAssignmentNotMatchingVarDocRule/narrowing-disabled.php');
    }

    public function testNarrowing(): void
    {
        $this->allowNarrowing = true;
        $this->analyseFile(__DIR__ . '/data/ForbidAssignmentNotMatchingVarDocRule/narrowing-enabled.php');
    }

}
