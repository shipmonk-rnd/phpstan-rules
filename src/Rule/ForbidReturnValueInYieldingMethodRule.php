<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function in_array;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class ForbidReturnValueInYieldingMethodRule implements Rule
{

    private bool $reportRegardlessOfReturnType;

    public function __construct(bool $reportRegardlessOfReturnType)
    {
        $this->reportRegardlessOfReturnType = $reportRegardlessOfReturnType;
    }

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
        if (!$node->getStatementResult()->hasYield()) {
            return [];
        }

        if (!$this->reportRegardlessOfReturnType) {
            $methodReturnType = $this->getReturnType($node, $scope);

            if (in_array(Generator::class, $methodReturnType->getObjectClassNames(), true)) {
                return [];
            }
        }

        $errors = [];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnNode = $returnStatement->getReturnNode();

            if ($returnNode->expr === null) {
                continue;
            }

            $suffix = $this->reportRegardlessOfReturnType
                ? 'this approach is denied'
                : 'but this method is not marked to return Generator';

            $callType = $node instanceof MethodReturnStatementsNode
                ? 'method'
                : 'function';

            $errors[] = RuleErrorBuilder::message("Returned value from yielding $callType can be accessed only via Generator::getReturn, $suffix.")
                ->line($returnNode->getStartLine())
                ->identifier('shipmonk.returnValueFromYieldingMethod')
                ->build();
        }

        return $errors;
    }

    private function getReturnType(
        ReturnStatementsNode $node,
        Scope $scope
    ): Type
    {
        $methodReflection = $scope->getFunction();

        if ($node instanceof ClosureReturnStatementsNode) {
            return $scope->getFunctionType($node->getClosureExpr()->getReturnType(), false, false);
        }

        if ($methodReflection !== null) {
            return $methodReflection->getReturnType();
        }

        return new MixedType();
    }

}
