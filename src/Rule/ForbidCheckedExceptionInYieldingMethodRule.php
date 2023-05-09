<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class ForbidCheckedExceptionInYieldingMethodRule implements Rule
{

    private DefaultExceptionTypeResolver $exceptionTypeResolver;

    public function __construct(DefaultExceptionTypeResolver $exceptionTypeResolver)
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
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

        $errors = [];

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in yielding method is denied as it gets thrown upon Generator iteration")
                        ->line($throwPoint->getNode()->getLine())
                        ->build();
                }
            }
        }

        return $errors;
    }

}
