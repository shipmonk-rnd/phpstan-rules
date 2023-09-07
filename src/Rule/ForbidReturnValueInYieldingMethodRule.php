<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;

/**
 * @implements Rule<MethodReturnStatementsNode>
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
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param MethodReturnStatementsNode $node
     * @return list<RuleError>
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
            $methodReturnType = ParametersAcceptorSelector::selectSingle($node->getMethodReflection()->getVariants())->getReturnType();

            if (in_array(Generator::class, $methodReturnType->getObjectClassNames(), true)) {
                return [];
            }
        }

        $errors = [];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnNode = $returnStatement->getReturnNode();

            if ($returnNode->expr !== null) {
                $suffix = $this->reportRegardlessOfReturnType
                    ? 'this approach is denied'
                    : 'but this method is not marked to return Generator';

                $errors[] = RuleErrorBuilder::message("Returned value from yielding method can be accessed only via Generator::getReturn, $suffix.")
                    ->line($returnNode->getLine())
                    ->build();
            }
        }

        return $errors;
    }

}
