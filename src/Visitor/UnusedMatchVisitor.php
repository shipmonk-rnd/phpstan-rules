<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_ as ThrowExpr;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

class UnusedMatchVisitor extends NodeVisitorAbstract
{

    public const MATCH_RESULT_USED = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'matchResultUsed';

    /**
     * @var Node[]
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
            $parent = $this->stack[count($this->stack) - 1];

            if ($node instanceof Match_ && $this->isUsed($parent)) {
                $node->setAttribute(self::MATCH_RESULT_USED, true);
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

    private function shouldBuildStack(Node $node): bool
    {
        return $this->stack !== [] || $this->isUsed($node);
    }

    /**
     * Those parent nodes are marking the match as used
     */
    private function isUsed(Node $parent): bool
    {
        return $parent instanceof Throw_
            || $parent instanceof Assign
            || $parent instanceof MethodCall
            || $parent instanceof Return_
            || $parent instanceof Arg
            || $parent instanceof Coalesce
            || $parent instanceof ArrayItem
            || $parent instanceof NullsafeMethodCall
            || $parent instanceof Ternary
            || $parent instanceof Yield_
            || $parent instanceof YieldFrom
            || $parent instanceof ThrowExpr;
    }

}
