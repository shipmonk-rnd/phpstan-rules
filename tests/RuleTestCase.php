<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStanDev\RuleTestCase as ShipMonkDevRuleTestCase;
use function is_array;

/**
 * @template TRule of Rule
 * @extends ShipMonkDevRuleTestCase<TRule>
 */
abstract class RuleTestCase extends ShipMonkDevRuleTestCase
{

    /**
     * @param string|list<string> $files
     */
    protected function analyseFile(
        $files,
        bool $autofix = false
    ): void
    {
        $this->analyzeFiles(is_array($files) ? $files : [$files], $autofix);
    }

}
