<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use PHPStan\Testing\PHPStanTestCase;
use function array_merge;

class NoReflectionInRuleConstructorTest extends PHPStanTestCase
{

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [
                __DIR__ . '/../rules.neon',
                __DIR__ . '/NoReflectionInRuleConstructorTest.neon',
            ],
        );
    }

    public function test(): void
    {
        self::expectNotToPerformAssertions();

        self::getContainer()->getServicesByTag('phpstan.rules.rule');
    }

}
