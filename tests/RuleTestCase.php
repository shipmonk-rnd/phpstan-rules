<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use LogicException;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase as OriginalRuleTestCase;
use function array_filter;
use function array_values;
use function explode;
use function file_get_contents;
use function preg_match_all;
use function preg_quote;
use function trim;

/**
 * @template TRule of Rule
 * @extends OriginalRuleTestCase<TRule>
 */
abstract class RuleTestCase extends OriginalRuleTestCase
{

    /**
     * @param list<string> $errorTags
     */
    protected function analyseFile(string $file, array $errorTags = []): void
    {
        $file = $this->getFileHelper()->normalizePath($file);
        $this->analyse([$file], $this->parseExpectedErrors($file, $errorTags));
    }

    /**
     * @param list<string> $errorTags
     * @return list<array{0: string, 1: int}>
     */
    private function parseExpectedErrors(string $file, array $errorTags): array
    {
        $fileData = file_get_contents($file);

        if ($fileData === false) {
            throw new LogicException('Error while reading data from ' . $file);
        }

        $fileDataLines = explode("\n", $fileData);

        $expectedErrors = [];

        foreach ($fileDataLines as $line => $row) {
            $expectedErrors[] = $this->getExpectedErrorForLine($line, $row, null);

            foreach ($errorTags as $errorTag) {
                $expectedErrors[] = $this->getExpectedErrorForLine($line, $row, $errorTag);
            }
        }

        return array_values(array_filter($expectedErrors));
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    private function getExpectedErrorForLine(int $line, string $row, ?string $errorTag): ?array
    {
        $matches = [];

        if ($errorTag === null) {
            $matched = preg_match_all('#// error:(.+)#', $row, $matches);
        } else {
            $errorTagRegex = preg_quote($errorTag, '#');
            $matched = preg_match_all('#// error \(' . $errorTagRegex . '\):(.+)#', $row, $matches);
        }

        if ($matched === false) {
            throw new LogicException('Error while matching errors');
        }

        if ($matched === 0) {
            return null;
        }

        foreach ($matches[1] as $error) {
            return [
                trim($error),
                $line + 1,
            ];
        }

        return null;
    }

}
