<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use DirectoryIterator;
use PHPStan\Testing\PHPStanTestCase;
use function array_keys;
use function array_merge;
use function get_class;
use function implode;
use function str_replace;

class AllRulesInConfigTest extends PHPStanTestCase
{

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/../rules.neon'],
        );
    }

    public function test(): void
    {
        self::expectNotToPerformAssertions();

        $existingRules = new DirectoryIterator(__DIR__ . '/../src/Rule');
        $existingRuleClassNames = [];

        /** @var DirectoryIterator $existingRule */
        foreach ($existingRules as $existingRule) {
            if (!$existingRule->isFile()) {
                continue;
            }

            $namespace = 'ShipMonk\\PHPStan\\Rule\\';
            $className = $namespace . str_replace('.php', '', $existingRule->getFilename());
            $existingRuleClassNames[$className] = true;
        }

        /** @var object[] $registeredRules */
        $registeredRules = self::getContainer()->getServicesByTag('phpstan.rules.rule');

        foreach ($registeredRules as $registeredRule) {
            unset($existingRuleClassNames[get_class($registeredRule)]);
        }

        if ($existingRuleClassNames !== []) {
            self::fail('Rule ' . implode(', ', array_keys($existingRuleClassNames)) . ' not registered in rules.neon');
        }
    }

}
