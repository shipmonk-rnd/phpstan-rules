<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Exceptions\ExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodReturnStatementsNode>
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
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param MethodReturnStatementsNode $node
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

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in yielding method is denied as it gets thrown upon Generator iteration")
                        ->line($throwPoint->getNode()->getStartLine())
                        ->identifier('shipmonk.checkedExceptionInYieldingMethod')
                        ->build();
                }
            }
        }

        return $errors;
    }

}
