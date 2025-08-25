<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStanDev\RuleTestCase as ShipMonkDevRuleTestCase;

/**
 * @template TRule of Rule
 * @extends ShipMonkDevRuleTestCase<TRule>
 */
abstract class RuleTestCase extends ShipMonkDevRuleTestCase
{

    public function analyseFile(
        string $file,
        bool $autofix = false
    ): void
    {
        $this->analyzeFiles([$file], $autofix);
    }

}
