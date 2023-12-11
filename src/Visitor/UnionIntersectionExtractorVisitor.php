<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitorAbstract;

class UnionIntersectionExtractorVisitor extends NodeVisitorAbstract
{

    /**
     * @var list<IntersectionType|UnionType>
     */
    private array $nodes = [];

    /**
     * @return list<IntersectionType|UnionType>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function enterNode(Node $node): Node
    {
        if ($node instanceof IntersectionType || $node instanceof UnionType) {
            $this->nodes[] = $node;
        }

        if ($node instanceof NullableType) {
            $this->nodes[] = new UnionType([$node->type, new Identifier('null')], $node->getAttributes());
        }

        return $node;
    }

}
