<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeWithClassName;
use ShipMonk\PHPStan\Visitor\UnusedExceptionVisitor;
use Throwable;

/**
 * @implements Rule<Expr>
 */
class ForbidUnusedExceptionRule implements Rule
{

    private Standard $printer;

    public function __construct(Standard $printer)
    {
        $this->printer = $printer;
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
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
     * @return list<string>
     */
    private function processCall(CallLike $node, Scope $scope): array
    {
        if (!$this->isException($node, $scope)) {
            return [];
        }

        if (!$this->isUsed($node)) {
            return ["Method {$this->printer->prettyPrintExpr($node)} returns exception that was not used in any way."];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function processNew(New_ $node, Scope $scope): array
    {
        if (!$this->isException($node, $scope)) {
            return [];
        }

        if (!$this->isUsed($node)) {
            return ["Exception {$this->printer->prettyPrintExpr($node)} was not used in any way."];
        }

        return [];
    }

    private function isException(Expr $node, Scope $scope): bool
    {
        $type = $scope->getType($node);

        return $type instanceof TypeWithClassName // TODO fix once getObjectClassReflections is ready?
            && $type->getClassReflection() !== null
            && $type->getClassReflection()->isSubclassOf(Throwable::class);
    }

    private function isUsed(Expr $node): bool
    {
        return $node->getAttribute(UnusedExceptionVisitor::RESULT_USED) === true;
    }

}
