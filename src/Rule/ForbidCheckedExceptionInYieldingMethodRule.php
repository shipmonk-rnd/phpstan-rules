<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Rules\Exceptions\ExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class ForbidCheckedExceptionInYieldingMethodRule implements Rule
{

    private ExceptionTypeResolver $exceptionTypeResolver;

    public function __construct(ExceptionTypeResolver $exceptionTypeResolver)
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
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

        $errors = [];
        $functionName = $this->getFunctionName($node);

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in yielding $functionName is denied as it gets thrown upon Generator iteration")
                        ->line($throwPoint->getNode()->getStartLine())
                        ->identifier('shipmonk.checkedExceptionInYieldingMethod')
                        ->build();
                }
            }
        }

        return $errors;
    }

    private function getFunctionName(ReturnStatementsNode $node): string
    {
        if ($node instanceof ClosureReturnStatementsNode) {
            return 'closure';
        }

        if ($node instanceof FunctionReturnStatementsNode) {
            return 'function';
        }

        if ($node instanceof PropertyHookReturnStatementsNode) {
            return 'property hook';
        }

        return 'method';
    }

}
