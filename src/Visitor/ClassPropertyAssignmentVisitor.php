<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

class ClassPropertyAssignmentVisitor extends NodeVisitorAbstract
{

    public const ASSIGNED_EXPR = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'assignment';

    /**
     * @var Node[]
     */
    private array $stack = [];

    /**
     * @param Node[] $nodes
     * @return Node[]
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

            if (
                $parent instanceof Assign
                && ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch)
            ) {
                $node->setAttribute(self::ASSIGNED_EXPR, $parent->expr);
            }

            $this->stack[] = $node;
        }

        if ($this->shouldBuildStack($node)) {
            $this->stack[] = $node;
        }

        return null;
    }

    private function shouldBuildStack(Node $node): bool
    {
        return $this->stack !== [] || $node instanceof Assign;
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);
        return null;
    }

}
