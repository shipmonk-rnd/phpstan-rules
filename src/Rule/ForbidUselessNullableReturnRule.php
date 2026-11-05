<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_filter;
use function array_values;
use function count;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class ForbidUselessNullableReturnRule implements Rule
{

    public function getNodeType(): string
    {
        return ReturnStatementsNode::class;
    }

    /**
     * @param ReturnStatementsNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope,
    ): array
    {
        if ($node instanceof PropertyHookReturnStatementsNode) {
            return []; // hooks cannot have their own return type, it is inherited from the property
        }

        $verbosity = VerbosityLevel::precise();
        $methodReflection = $scope->getFunction();

        if ($methodReflection instanceof MethodReflection && $this->isOverridable($methodReflection)) {
            return [];
        }

        if ($node instanceof ClosureReturnStatementsNode) {
            $declaredType = $scope->getFunctionType($node->getClosureExpr()->getReturnType(), false, false);
        } elseif ($methodReflection !== null) {
            $declaredType = $methodReflection->getReturnType();
        } else {
            return [];
        }

        if ($declaredType->isVoid()->yes()) {
            return [];
        }

        $allReturnTypes = [];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnExpression = $returnStatement->getReturnNode()->expr;

            if ($returnExpression === null) {
                $returnedType = new NullType();
            } else {
                $returnedType = $returnStatement->getScope()->getType($returnExpression);
            }

            $allReturnTypes[] = $returnedType;
        }

        $returnTypeUnion = TypeCombinator::union(...$allReturnTypes);

        if ($returnTypeUnion instanceof MixedType || $returnTypeUnion instanceof NeverType) {
            return [];
        }

        if (TypeCombinator::containsNull($declaredType) && !TypeCombinator::containsNull($returnTypeUnion)) {
            $error = RuleErrorBuilder::message("Declared return type {$declaredType->describe($verbosity)} contains null, but it is never returned. Returned types: {$returnTypeUnion->describe($verbosity)}.")
                ->identifier('shipmonk.uselessNullableReturn');

            $returnTypeNode = $this->getNativeReturnTypeNode($node);

            if (
                ($returnTypeNode instanceof NullableType || $returnTypeNode instanceof UnionType)
                && ($node instanceof ClosureReturnStatementsNode || !$this->hasNullInPhpDocReturnType($scope))
            ) {
                $error->fixNode($returnTypeNode, $this->removeNullFromTypeNode(...));
            }

            return [$error->build()];
        }

        return [];
    }

    private function getNativeReturnTypeNode(ReturnStatementsNode $node): ?Node
    {
        return match (true) {
            $node instanceof ClosureReturnStatementsNode => $node->getClosureExpr()->returnType,
            $node instanceof MethodReturnStatementsNode => $node->getClassMethod()->returnType,
            $node instanceof FunctionReturnStatementsNode => $node->getFunction()->returnType,
            default => null,
        };
    }

    /**
     * Removing null only from the native type would make it narrower than the PHPDoc type.
     */
    private function hasNullInPhpDocReturnType(Scope $scope): bool
    {
        $function = $scope->getFunction();

        if ($function === null) {
            return true;
        }

        return TypeCombinator::containsNull($function->getOnlyVariant()->getPhpDocReturnType());
    }

    private function removeNullFromTypeNode(NullableType|UnionType $typeNode): Node
    {
        if ($typeNode instanceof NullableType) {
            return $typeNode->type;
        }

        $types = array_values(array_filter(
            $typeNode->types,
            static fn (Node $type): bool => !$type instanceof Identifier || $type->toLowerString() !== 'null',
        ));

        if (count($types) === 1) {
            return $types[0];
        }

        $typeNode->types = $types;
        return $typeNode;
    }

    private function isOverridable(MethodReflection $methodReflection): bool
    {
        return !$methodReflection->isFinal()->yes() && !$methodReflection->isPrivate() && !$methodReflection->isStatic() && !$methodReflection->getDeclaringClass()->isFinalByKeyword();
    }

}
