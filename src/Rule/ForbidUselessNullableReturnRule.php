<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class ForbidUselessNullableReturnRule implements Rule
{

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param MethodReturnStatementsNode $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $verbosity = VerbosityLevel::precise();
        $methodReflection = $scope->getFunction();

        if (!$methodReflection instanceof MethodReflection) {
            return [];
        }

        $declaredType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

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
