<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FileNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Rules\Exceptions\ExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ShipMonk\PHPStan\Helper\ImmediatelyInvokedCallableHelper;
use function spl_object_hash;

/**
 * @implements Rule<Node>
 */
class ForbidCheckedExceptionInYieldingMethodRule implements Rule
{

    private ExceptionTypeResolver $exceptionTypeResolver;

    private ImmediatelyInvokedCallableHelper $callableHelper;

    /**
     * @var array<string, true>
     */
    private array $immediatelyInvokedClosures = [];

    /**
     * @var list<ClosureReturnStatementsNode>
     */
    private array $pendingClosures = [];

    public function __construct(
        ExceptionTypeResolver $exceptionTypeResolver,
        ImmediatelyInvokedCallableHelper $callableHelper
    )
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
        $this->callableHelper = $callableHelper;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $errors = [];

        if ($node instanceof FileNode) {
            $this->immediatelyInvokedClosures = [];
            $this->pendingClosures = [];

        } elseif ($node instanceof CallLike) {
            $this->immediatelyInvokedClosures += $this->callableHelper->getImmediatelyInvokedHashes($node, $scope);

        } elseif ($node instanceof ClosureReturnStatementsNode) {
            if ($node->getStatementResult()->hasYield()) {
                $this->pendingClosures[] = $node;
            }

        } elseif ($node instanceof ReturnStatementsNode) {
            if ($node->getStatementResult()->hasYield()) {
                $errors = $this->checkThrowPoints($node);
            }
        }

        if (!$scope->isInClass() || $node instanceof ClassMethodsNode) {
            foreach ($this->pendingClosures as $closureNode) {
                $closureHash = spl_object_hash($closureNode->getClosureExpr());

                if (isset($this->immediatelyInvokedClosures[$closureHash])) {
                    foreach ($this->checkThrowPoints($closureNode) as $error) {
                        $errors[] = $error;
                    }
                }
            }

            $this->pendingClosures = [];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkThrowPoints(ReturnStatementsNode $node): array
    {
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
