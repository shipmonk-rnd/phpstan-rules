<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function count;
use function implode;

/**
 * @template-implements Rule<FuncCall>
 */
class ForbidEnumInFunctionArgumentsRule implements Rule
{

    private const ANY_ARGUMENT = -1;

    private const REASON_IMPLICIT_TO_STRING = 'as the function causes implicit __toString conversion which is not supported for enums';
    private const REASON_UNPREDICTABLE_RESULT = 'as the function causes unexpected results'; // https://3v4l.org/YtGVa
    private const REASON_SKIPS_ENUMS = 'as the function will skip any enums and produce warning';

    private const FUNCTION_MAP = [
        'array_intersect' => [self::ANY_ARGUMENT, self::REASON_IMPLICIT_TO_STRING],
        'array_intersect_assoc' => [self::ANY_ARGUMENT, self::REASON_IMPLICIT_TO_STRING],
        'array_diff' => [self::ANY_ARGUMENT, self::REASON_IMPLICIT_TO_STRING],
        'array_diff_assoc' => [self::ANY_ARGUMENT, self::REASON_IMPLICIT_TO_STRING],
        'array_unique' => [0, self::REASON_IMPLICIT_TO_STRING],
        'array_combine' => [0, self::REASON_IMPLICIT_TO_STRING],
        'sort' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'asort' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'arsort' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'natsort' => [0, self::REASON_IMPLICIT_TO_STRING],
        'array_count_values' => [0, self::REASON_SKIPS_ENUMS],
        'array_fill_keys' => [0, self::REASON_IMPLICIT_TO_STRING],
        'array_flip' => [0, self::REASON_SKIPS_ENUMS],
        'array_product' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'array_sum' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'implode' => [1, self::REASON_IMPLICIT_TO_STRING],
    ];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $functionName = $node->name->toLowerString();

        if (!array_key_exists($functionName, self::FUNCTION_MAP)) {
            return [];
        }

        [$forbiddenArgumentPosition, $reason] = self::FUNCTION_MAP[$functionName];

        $wrongArguments = [];

        foreach ($node->getArgs() as $position => $argument) {
            if (!$this->matchesPosition((int) $position, $forbiddenArgumentPosition)) {
                continue;
            }

            $argumentType = $scope->getType($argument->value);

            if ($this->containsEnum($argumentType)) {
                $wrongArguments[] = $position + 1;
            }
        }

        if ($wrongArguments !== []) {
            $plural = count($wrongArguments) > 1 ? 's' : '';
            $wrongArgumentsString = implode(', ', $wrongArguments);
            return ["Argument{$plural} {$wrongArgumentsString} in {$node->name->toString()}() cannot contain enum {$reason}"];
        }

        return [];
    }

    private function matchesPosition(int $position, int $forbiddenArgumentPosition): bool
    {
        if ($forbiddenArgumentPosition === self::ANY_ARGUMENT) {
            return true;
        }

        return $position === $forbiddenArgumentPosition;
    }

    private function containsEnum(Type $type): bool
    {
        if ($type->isArray()->yes() && $this->containsEnum($type->getIterableValueType())) {
            return true;
        }

        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($this->containsEnum($innerType)) {
                    return true;
                }
            }

            return false;
        }

        return $type->isEnum()->yes();
    }

}
