<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
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
        Scope $scope
    ): array
    {
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
                ->identifier('shipmonk.uselessNullableReturn')
                ->build();
            return [$error];
        }

        return [];
    }

    private function isOverridable(MethodReflection $methodReflection): bool
    {
        return !$methodReflection->isFinal()->yes() && !$methodReflection->isPrivate() && !$methodReflection->isStatic() && !$methodReflection->getDeclaringClass()->isFinalByKeyword();
    }

}
