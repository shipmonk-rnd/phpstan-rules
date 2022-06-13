<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use LogicException;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase as OriginalRuleTestCase;
use function explode;
use function file_get_contents;
use function preg_match_all;
use function trim;

/**
 * @template TRule of Rule
 * @extends OriginalRuleTestCase<TRule>
 */
abstract class RuleTestCase extends OriginalRuleTestCase
{

    protected function analyseFile(string $file): void
    {
        $file = $this->getFileHelper()->normalizePath($file);
        $this->analyse([$file], $this->parseExpectedErrors($file));
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function parseExpectedErrors(string $file): array
    {
        $fileData = file_get_contents($file);

        if ($fileData === false) {
            throw new LogicException('Error while reading data from ' . $file);
        }

        $fileData = explode("\n", $fileData);

        $expectedErrors = [];

        foreach ($fileData as $line => $row) {
            $matches = [];
            $matched = preg_match_all('#// error:([^$]+)#', $row, $matches);

            if ($matched === false) {
                throw new LogicException('Error while matching errors');
            }

            if ($matched === 0) {
                continue;
            }

            foreach ($matches[1] as $error) {
                $expectedErrors[] = [
                    trim($error),
                    $line + 1,
                ];
            }
        }

        return $expectedErrors;
    }

}
