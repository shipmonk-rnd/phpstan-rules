<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

class NamedArgumentSourceVisitor extends NodeVisitorAbstract
{

    public const IS_ATTRIBUTE_NAMED_ARGUMENT = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'is_attribute_argument';

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
                $parent instanceof Attribute
                && $node instanceof Arg
                && $node->name !== null
            ) {
                $node->setAttribute(self::IS_ATTRIBUTE_NAMED_ARGUMENT, true);
            }
        }

        if ($this->shouldBuildStack($node)) { // start adding to stack once Attribute is reached
            $this->stack[] = $node;
        }

        return null;
    }

    private function shouldBuildStack(Node $node): bool
    {
        return $this->stack !== [] || $node instanceof Attribute;
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);
        return null;
    }

}
