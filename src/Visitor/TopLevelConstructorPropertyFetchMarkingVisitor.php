<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

class TopLevelConstructorPropertyFetchMarkingVisitor extends NodeVisitorAbstract
{

    public const IS_TOP_LEVEL_CONSTRUCTOR_FETCH_ASSIGNMENT = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'topLevelConstructorFetchAssignment';

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
        $nodesInStack = count($this->stack);

        if (
            $nodesInStack >= 3
            && $node instanceof PropertyFetch
            && $this->stack[$nodesInStack - 1] instanceof Assign // @phpstan-ignore offsetAccess.notFound
            && $this->stack[$nodesInStack - 2] instanceof Expression // @phpstan-ignore offsetAccess.notFound
            && $this->stack[$nodesInStack - 3] instanceof ClassMethod // @phpstan-ignore offsetAccess.notFound
            && $this->stack[$nodesInStack - 3]->name->name === '__construct'
        ) {
            $node->setAttribute(self::IS_TOP_LEVEL_CONSTRUCTOR_FETCH_ASSIGNMENT, true);
        }

        if ($this->shouldBuildStack($node)) {
            $this->stack[] = $node;
        }

        return null;
    }

    private function shouldBuildStack(Node $node): bool
    {
        return $this->stack !== [] || ($node instanceof ClassMethod && $node->name->name === '__construct');
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);
        return null;
    }

}
