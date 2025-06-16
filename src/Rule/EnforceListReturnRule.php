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
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $methodReflection = $scope->getFunction();

        if ($methodReflection === null || $node instanceof ClosureReturnStatementsNode) {
            return [];
        }

        $returnType = $methodReflection->getReturnType();

        if ($this->alwaysReturnList($node) && !$returnType->isList()->yes()) {
            $callLikeType = $methodReflection instanceof MethodReflection
                ? 'Method'
                : 'Function';
            $returnTypeString = $returnType->describe(VerbosityLevel::precise());

            $error = RuleErrorBuilder::message("{$callLikeType} {$methodReflection->getName()} always return list, but is marked as {$returnTypeString}")
                ->identifier('shipmonk.returnListNotUsed')
                ->build();
            return [$error];
        }

        return [];
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

}
