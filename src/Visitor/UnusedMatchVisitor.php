<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function end;

class UnusedMatchVisitor extends NodeVisitorAbstract
{

    public const MATCH_RESULT_USED = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'matchResultUsed';

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
        if ($node instanceof Match_ && !$this->hasUnusedResult()) {
            $node->setAttribute(self::MATCH_RESULT_USED, true);
        }

        $this->stack[] = $node;
        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);
        return null;
    }

    private function hasUnusedResult(): bool
    {
        $parent = end($this->stack);
        return $parent === false || $parent instanceof Expression;
    }

}
