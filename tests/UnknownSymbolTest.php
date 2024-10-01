<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Testing\PHPStanTestCase;
use function array_map;
use function array_merge;

class UnknownSymbolTest extends PHPStanTestCase
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

    public function testNoInternalError(): void
    {
        $errors = $this->runAnalyser([__DIR__ . '/Rule/data/unknown-symbol.php']);

        $internalErrors = [];

        foreach ($errors as $error) {
            if ($error->getIdentifier() === 'phpstan.internal') {
                $internalErrors[] = $error->getMessage();
            }
        }

        self::assertSame([], $internalErrors);
    }

    /**
     * @param list<string> $filePaths
     * @return list<Error>
     */
    private function runAnalyser(array $filePaths): array
    {
        $analyser = self::getContainer()->getByType(Analyser::class); // @phpstan-ignore phpstanApi.classConstant
        $finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class); // @phpstan-ignore phpstanApi.classConstant

        $normalizedFilePaths = array_map(fn (string $path): string => $this->getFileHelper()->normalizePath($path), $filePaths);

        $analyserResult = $analyser->analyse($normalizedFilePaths);
        $analyserResult = $finalizer->finalize($analyserResult, true, false)->getAnalyserResult();

        return $analyserResult->getErrors();
    }

}
