<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function end;

class UnusedExceptionVisitor extends NodeVisitorAbstract
{

    public const RESULT_USED = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'resultUsed';

    /**
     * @var list<Node>
     */
    private array $stack = [];

    /**
     * @param Node[] $nodes
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->stack = [];
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($this->stack !== []) {
            $parent = end($this->stack);

            if ($this->isNodeInInterest($node) && $this->isUsed($parent)) {
                $node->setAttribute(self::RESULT_USED, true);
            }
        }

        if ($this->shouldBuildStack($node)) {
            $this->stack[] = $node;
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);
        return null;
    }

    /**
     * Those nodes *may* return exception
     * - should match those in UnusedExceptionRule
     */
    private function isNodeInInterest(Node $node): bool
    {
        return $node instanceof New_
            || $node instanceof MethodCall
            || $node instanceof StaticCall;
    }

    private function shouldBuildStack(Node $node): bool
    {
        return $this->stack !== [] || $this->isUsed($node);
    }

    /**
     * Those parent nodes are marking the exception as used
     */
    private function isUsed(Node $parent): bool
    {
        return $parent instanceof Assign
            || $parent instanceof MethodCall
            || $parent instanceof Return_
            || $parent instanceof Arg
            || $parent instanceof Coalesce
            || $parent instanceof ArrayItem
            || $parent instanceof NullsafeMethodCall
            || $parent instanceof Ternary
            || $parent instanceof Yield_
            || $parent instanceof Throw_
            || $parent instanceof ClassConstFetch
            || $parent instanceof MatchArm;
    }

}
