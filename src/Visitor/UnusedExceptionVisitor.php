<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
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
        if ($this->isNodeInInterest($node) && !$this->hasUnusedResult()) {
            $node->setAttribute(self::RESULT_USED, true);
        }

        $this->stack[] = $node;
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

    private function hasUnusedResult(): bool
    {
        $parent = end($this->stack);
        return $parent === false || $parent instanceof Expression;
    }

}
