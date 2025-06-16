<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\TryCatch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function is_string;

/**
 * @implements Rule<TryCatch>
 */
class RequirePreviousExceptionPassRule implements Rule
{

    private Printer $printer;

    private bool $reportEvenIfExceptionIsNotAcceptableByRethrownOne;

    public function __construct(
        Printer $printer,
        bool $reportEvenIfExceptionIsNotAcceptableByRethrownOne = false
    )
    {
        $this->printer = $printer;
        $this->reportEvenIfExceptionIsNotAcceptableByRethrownOne = $reportEvenIfExceptionIsNotAcceptableByRethrownOne;
    }

    public function getNodeType(): string
    {
        return TryCatch::class;
    }

    /**
     * @param TryCatch $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $errors = [];
        $previouslyCaughtExceptionsUnion = new NeverType();

        foreach ($node->catches as $catch) {
            $caughtExceptionType = $this->getCaughtExceptionType($catch->types, $scope, $previouslyCaughtExceptionsUnion);
            $previouslyCaughtExceptionsUnion = TypeCombinator::union($caughtExceptionType, $previouslyCaughtExceptionsUnion);

            $caughtExceptionVariableName = $catch->var === null ? null : $catch->var->name;

            if (!is_string($caughtExceptionVariableName) && $caughtExceptionVariableName !== null) {
                return [];
            }

            foreach ($catch->stmts as $statement) {
                if ($statement instanceof Expression && $statement->expr instanceof Throw_) {
                    $throwExpression = $statement->expr->expr;
                } else {
                    continue;
                }

                if ($throwExpression instanceof CallLike) {
                    $errors = array_merge(
                        $errors,
                        $this->processExceptionCreation(
                            $scope->isDeclareStrictTypes(),
                            $caughtExceptionVariableName,
                            $caughtExceptionType,
                            $throwExpression,
                            $scope,
                        ),
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processExceptionCreation(
        bool $strictTypes,
        ?string $caughtExceptionVariableName,
        Type $caughtExceptionType,
        CallLike $node,
        Scope $scope
    ): array
    {
        $passed = false;

        foreach ($node->getArgs() as $argument) {
            if (!$argument->value instanceof Variable) {
                continue; // support only simple variable pass
            }

            $argumentVariableName = $argument->value->name;

            if (!is_string($argumentVariableName)) {
                continue;
            }

            if ($caughtExceptionVariableName === null) {
                continue;
            }

            if ($caughtExceptionVariableName === $argumentVariableName) {
                $passed = true;
            }
        }

        if (!$this->reportEvenIfExceptionIsNotAcceptableByRethrownOne) {
            $accepts = false;

            foreach ($this->getCallLikeParameters($node, $scope) as $parameter) {
                if ($parameter->getType()->accepts($caughtExceptionType, $strictTypes)->yes()) {
                    $accepts = true;
                }
            }
        } else {
            $accepts = true;
        }

        if (!$passed && $accepts) {
            $exceptionName = $caughtExceptionVariableName === null ? "({$caughtExceptionType->describe(VerbosityLevel::typeOnly())})" : "\${$caughtExceptionVariableName}";
            $error = RuleErrorBuilder::message("Exception {$exceptionName} not passed as previous to {$this->printer->prettyPrintExpr($node)}")
                ->line($node->getStartLine())
                ->identifier('shipmonk.missingPreviousException')
                ->build();
            return [$error];
        }

        return [];
    }

    /**
     * @return list<ParameterReflection>
     */
    private function getCallLikeParameters(
        CallLike $node,
        Scope $scope
    ): array
    {
        $methodReflection = null;

        if (
            ($node instanceof StaticCall || $node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Identifier
        ) {
            $methodReflection = $scope->getMethodReflection($scope->getType($node), $node->name->name);
        }

        if ($node instanceof New_) {
            $methodReflection = $scope->getMethodReflection($scope->getType($node), '__construct');
        }

        // FuncCall not yet supported
        if ($methodReflection !== null) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $methodReflection->getVariants(),
            )->getParameters();
        }

        return [];
    }

    /**
     * @param Name[] $exceptionNames
     */
    private function getCaughtExceptionType(
        array $exceptionNames,
        Scope $scope,
        Type $exceptionTypesCaughtInPreviousCatches
    ): Type
    {
        $classes = [];

        foreach ($exceptionNames as $exceptionName) {
            $className = $scope->resolveName($exceptionName);
            $classes[] = new ObjectType($className, null, null);
        }

        if (count($classes) === 1) {
            return $classes[0];
        }

        return TypeCombinator::remove(
            TypeCombinator::union(...$classes),
            $exceptionTypesCaughtInPreviousCatches,
        );
    }

}
