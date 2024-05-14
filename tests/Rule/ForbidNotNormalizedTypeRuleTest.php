<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidNotNormalizedTypeRule>
 */
class ForbidNotNormalizedTypeRuleTest extends RuleTestCase
{

    protected function getRule(): ForbidNotNormalizedTypeRule
    {
        return new ForbidNotNormalizedTypeRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            self::getContainer()->getByType(TypeNodeResolver::class),
            self::getContainer()->getByType(Standard::class),
            true,
        );
    }

    public function testRule(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNotNormalizedTypeRule/code.php');
    }

}
