<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Extension;

use PHPStan\Testing\TypeInferenceTestCase;

class ImmediatelyCalledCallableThrowTypeExtensionTest extends TypeInferenceTestCase
{

    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/ImmediatelyCalledCallableThrowTypeExtension/code.php');
    }

    /**
     * @dataProvider dataFileAsserts
     * @param mixed ...$args
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        ...$args
    ): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/data/ImmediatelyCalledCallableThrowTypeExtension/extension.neon',
        ];
    }

}
