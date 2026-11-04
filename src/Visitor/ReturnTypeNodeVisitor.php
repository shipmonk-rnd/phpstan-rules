<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;

/**
 * PHPStan copies the attributes of a method or function to its ReturnStatementsNode, but does not expose the return type node there.
 */
class ReturnTypeNodeVisitor extends NodeVisitorAbstract
{

    public const RETURN_TYPE_NODE = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'returnTypeNode';

    public function enterNode(Node $node): ?Node
    {
        if (($node instanceof ClassMethod || $node instanceof Function_) && $node->returnType !== null) {
            $node->setAttribute(self::RETURN_TYPE_NODE, $node->returnType);
        }

        return null;
    }

}
