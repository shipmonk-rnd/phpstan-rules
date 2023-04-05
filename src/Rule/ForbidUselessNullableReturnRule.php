<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
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
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $verbosity = VerbosityLevel::precise();
        $methodReflection = $scope->getFunction();

        if ($node instanceof ClosureReturnStatementsNode) { // @phpstan-ignore-line ignore bc promise
            $declaredType = $scope->getFunctionType($node->getClosureExpr()->getReturnType(), false, false);
        } elseif ($methodReflection !== null) {
            $declaredType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
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
            return ["Declared return type {$declaredType->describe($verbosity)} contains null, but it is never returned. Returned types: {$returnTypeUnion->describe($verbosity)}."];
        }

        return [];
    }

}
