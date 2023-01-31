<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function count;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class EnforceListReturnRule implements Rule
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
        if (AccessoryArrayListType::isListTypeEnabled() === false) {
            return [];
        }

        if ($scope->getClassReflection() === null) {
            return [];
        }

        $method = $scope->getFunction();

        if (!$method instanceof MethodReflection) {
            return [];
        }

        if ($this->alwaysReturnList($node) && !$this->isMarkedWithListReturn($method)) {
            return ["Method {$method->getName()} always return list, but is marked as {$this->getReturnPhpDoc($method)}"];
        }

        return [];
    }

    private function getReturnPhpDoc(MethodReflection $methodReflection): string
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        return $returnType->describe(VerbosityLevel::precise());
    }

    private function alwaysReturnList(MethodReturnStatementsNode $node): bool
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

            if ($returnStatementsCount === 1 && $returnType instanceof ArrayType && $returnType->getItemType() instanceof NeverType) {
                return false; // do not consider empty array as list when it is the only return statement
            }
        }

        return true;
    }

    private function isMarkedWithListReturn(MethodReflection $methodReflection): bool
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        return $returnType->isList()->yes();
    }

}
