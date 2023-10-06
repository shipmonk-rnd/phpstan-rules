<?php declare(strict_types = 1);

namespace ForbidPhpDocNotMatchingNativeTypehintRule;


class HelloWorld
{

    /**
     * @var int
     */
    private ?int $nullMissingInPhpDoc; // error: The @var phpdoc does not contain null, but native return type does

    /**
     * @var int|null
     */
    private int $nullMissingInNativeType;

    /**
     * @var int
     */
    private int $okProperty;

    private int $okProperty2;

    /**
     * @var int
     */
    private $okProperty3;

    /**
     * @return int[]
     */
    public function nullMissingInPhpDocReturn(): ?array // error: The @return phpdoc does not contain null, but native return type does
    {
        return [];
    }

    /**
     * @return int[]|null
     */
    public function nullMissingInNativeReturn(): array
    {
        return [];
    }

    /**
     * @param int $foo
     */
    public function nullMissingInPhpDocParam(?int $foo, string $okParam): void // error: The @param $foo phpdoc does not contain null, but native return type does
    {
    }

    /**
     * @param int|null $bar
     */
    public function nullMissingInNativeParam(bool $fine, int $bar): void
    {

    }

    /**
     * @param int $noNative
     * @return int
     */
    public function noNativeTypehint($noNative)
    {
        return 1;
    }

    /**
     * @param string[] $mixedNative
     * @return string[]
     */
    public function mixedNative(mixed $mixedNative): mixed
    {
        return 1;
    }

    /**
     * @phpstan-return ($originalMoney is null ? null : float)
     */
    public function testConditionTypeForParameter(?float $originalMoney): ?float
    {
        return $originalMoney;
    }

    /**
     * @template T as string|null
     * @phpstan-param T $stringOrNullInTemplate
     * @phpstan-return T
     */
    public static function getStringOrNull($stringOrNullInTemplate): ?string
    {
        return $stringOrNullInTemplate;
    }

    /**
     * @template T as string
     * @phpstan-param T $stringOrNullInTemplate
     * @phpstan-return T
     */
    public static function getStringMissingNull($stringOrNullInTemplate): ?string // error: The @return phpdoc does not contain null, but native return type does
    {
        return $stringOrNullInTemplate;
    }

}
