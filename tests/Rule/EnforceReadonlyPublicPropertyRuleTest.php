<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

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
        if (PHP_VERSION_ID < 8_00_00) {
            self::markTestSkipped('PHP7 parser fails with property hooks');
        }

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
        return new PhpVersion($version);
    }

}
