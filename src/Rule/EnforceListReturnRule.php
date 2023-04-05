<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\VerbosityLevel;
use function count;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class EnforceListReturnRule implements Rule
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
        if (AccessoryArrayListType::isListTypeEnabled() === false) {
            return [];
        }

        $methodReflection = $scope->getFunction();

        if ($methodReflection === null || $node instanceof ClosureReturnStatementsNode) { // @phpstan-ignore-line ignore bc promise
            return [];
        }

        if ($this->alwaysReturnList($node) && !$this->isMarkedWithListReturn($methodReflection)) {
            $callLikeType = $methodReflection instanceof MethodReflection
                ? 'Method'
                : 'Function';

            return ["{$callLikeType} {$methodReflection->getName()} always return list, but is marked as {$this->getReturnPhpDoc($methodReflection)}"];
        }

        return [];
    }

    /**
     * @param FunctionReflection|MethodReflection $methodReflection
     */
    private function getReturnPhpDoc(object $methodReflection): string
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        return $returnType->describe(VerbosityLevel::precise());
    }

    private function alwaysReturnList(ReturnStatementsNode $node): bool
    {
        $returnStatementsCount = count($node->getReturnStatements());

        if ($returnStatementsCount === 0) {
            return false;
        }

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnExpr = $returnStatement->getReturnNode()->expr;

            if ($returnExpr === null) {
                return false;
            }

            $returnType = $returnStatement->getScope()->getType($returnExpr);

            if (!$returnType->isList()->yes()) {
                return false;
            }

            if ($returnStatementsCount === 1 && $returnType->isArray()->yes() && $returnType->isIterableAtLeastOnce()->no()) {
                return false; // do not consider empty array as list when it is the only return statement
            }
        }

        return true;
    }

    /**
     * @param FunctionReflection|MethodReflection $methodReflection
     */
    private function isMarkedWithListReturn(object $methodReflection): bool
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        return $returnType->isList()->yes();
    }

}
