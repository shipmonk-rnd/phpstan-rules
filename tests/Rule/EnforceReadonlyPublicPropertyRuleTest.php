<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<EnforceReadonlyPublicPropertyRule>
 */
class EnforceReadonlyPublicPropertyRuleTest extends RuleTestCase
{

    private ?PhpVersion $phpVersion = null;

    protected function getRule(): Rule
    {
        self::assertNotNull($this->phpVersion);
        return new EnforceReadonlyPublicPropertyRule($this->phpVersion);
    }

    public function testPhp84(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_400);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-84.php');
    }

    public function testPhp81(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_100);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-81.php');
    }

    public function testPhp80(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_000);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-80.php');
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version); // @phpstan-ignore phpstanApi.constructor
    }

}
