<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnforceReadonlyPublicPropertyRule>
 */
class EnforceReadonlyPublicPropertyRuleTest extends RuleTestCase
{

    private ?bool $excludePropertyWithDefaultValue = null;

    private ?PhpVersion $phpVersion = null;

    protected function getRule(): Rule
    {
        if ($this->excludePropertyWithDefaultValue === null) {
            throw new LogicException('excludePropertyWithDefaultValue must be set');
        }

        if ($this->phpVersion === null) {
            throw new LogicException('phpVersion must be set');
        }

        return new EnforceReadonlyPublicPropertyRule(
            $this->excludePropertyWithDefaultValue,
            $this->phpVersion,
        );
    }

    public function testPhp84(): void
    {
        if (PHP_VERSION_ID < 8_00_00) {
            self::markTestSkipped('PHP7 parser fails with property hooks');
        }

        $this->excludePropertyWithDefaultValue = false;
        $this->phpVersion = $this->createPhpVersion(80_400);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-84.php');
    }

    public function testPhp81(): void
    {
        $this->excludePropertyWithDefaultValue = false;
        $this->phpVersion = $this->createPhpVersion(80_100);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-81.php');
    }

    public function testPhp80(): void
    {
        $this->excludePropertyWithDefaultValue = false;
        $this->phpVersion = $this->createPhpVersion(80_000);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/code-80.php');
    }

    public function testExcludePropertyWithDefaultValue(): void
    {
        $this->excludePropertyWithDefaultValue = true;
        $this->phpVersion = $this->createPhpVersion(80_100);
        $this->analyseFile(__DIR__ . '/data/EnforceReadonlyPublicPropertyRule/exclude-property-with-default-value.php');
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version);
    }

}
