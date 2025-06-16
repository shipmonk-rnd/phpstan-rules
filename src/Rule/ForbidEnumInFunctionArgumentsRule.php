<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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

    private const REASON_UNPREDICTABLE_RESULT = 'as the function causes unexpected results'; // https://3v4l.org/YtGVa

    /**
     * Function name -> [forbidden argument position, reason]]
     */
    private const FUNCTION_MAP = [
        'sort' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'asort' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'arsort' => [0, self::REASON_UNPREDICTABLE_RESULT],

        // https://github.com/phpstan/phpstan/issues/11883
        'array_product' => [0, self::REASON_UNPREDICTABLE_RESULT],
        'array_sum' => [0, self::REASON_UNPREDICTABLE_RESULT],
    ];

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
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

        $functionReflection = $this->getFunctionReflection($node->name, $scope);

        if ($functionReflection === null) {
            return [];
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $functionReflection->getVariants());
        $funcCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

        if ($funcCall === null) {
            $funcCall = $node;
        }

        foreach ($funcCall->getArgs() as $position => $argument) {
            $argumentType = $scope->getType($argument->value);

            if (!$this->matchesPosition((int) $position, $forbiddenArgumentPosition)) {
                continue;
            }

            if ($this->containsEnum($argumentType)) {
                $wrongArguments[] = (int) $position + 1;
            }
        }

        if ($wrongArguments !== []) {
            $plural = count($wrongArguments) > 1 ? 's' : '';
            $wrongArgumentsString = implode(', ', $wrongArguments);
            $error = RuleErrorBuilder::message("Argument{$plural} {$wrongArgumentsString} in {$node->name->toString()}() cannot contain enum {$reason}")
                ->identifier('shipmonk.dangerousEnumArgument')
                ->build();
            return [$error];
        }

        return [];
    }

    private function matchesPosition(
        int $position,
        int $forbiddenArgumentPosition
    ): bool
    {
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

    private function getFunctionReflection(
        Name $functionName,
        Scope $scope
    ): ?FunctionReflection
    {
        return $this->reflectionProvider->hasFunction($functionName, $scope)
            ? $this->reflectionProvider->getFunction($functionName, $scope)
            : null;
    }

}
