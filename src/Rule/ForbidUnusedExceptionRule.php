<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ShipMonk\PHPStan\Visitor\UnusedExceptionVisitor;
use Throwable;

/**
 * @implements Rule<Expr>
 */
class ForbidUnusedExceptionRule implements Rule
{

    private Printer $printer;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            return $this->processCall($node, $scope);
        }

        if ($node instanceof New_) {
            return $this->processNew($node, $scope);
        }

        return [];
    }

    /**
     * @param MethodCall|StaticCall $node
     * @return list<IdentifierRuleError>
     */
    private function processCall(
        CallLike $node,
        Scope $scope
    ): array
    {
        if (!$this->isException($node, $scope)) {
            return [];
        }

        if (!$this->isUsed($node)) {
            $error = RuleErrorBuilder::message("Method {$this->printer->prettyPrintExpr($node)} returns exception that was not used in any way.")
                ->identifier('shipmonk.unusedException')
                ->build();
            return [$error];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processNew(
        New_ $node,
        Scope $scope
    ): array
    {
        if (!$this->isException($node, $scope)) {
            return [];
        }

        if (!$this->isUsed($node)) {
            $error = RuleErrorBuilder::message("Exception {$this->printer->prettyPrintExpr($node)} was not used in any way.")
                ->identifier('shipmonk.unusedException')
                ->build();
            return [$error];
        }

        return [];
    }

    private function isException(
        Expr $node,
        Scope $scope
    ): bool
    {
        $type = $scope->getType($node);

        foreach ($type->getObjectClassReflections() as $classReflection) {
            if ($classReflection->is(Throwable::class)) {
                return true;
            }
        }

        return false;
    }

    private function isUsed(Expr $node): bool
    {
        return $node->getAttribute(UnusedExceptionVisitor::RESULT_USED) === true;
    }

}
