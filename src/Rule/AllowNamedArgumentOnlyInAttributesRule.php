<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\Visitor\NamedArgumentSourceVisitor;

/**
 * @implements Rule<Arg>
 */
class AllowNamedArgumentOnlyInAttributesRule implements Rule
{

    public function getNodeType(): string
    {
        return Arg::class;
    }

    /**
     * @param Arg $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        if ($node->getAttribute(NamedArgumentSourceVisitor::IS_ATTRIBUTE_NAMED_ARGUMENT) === true) {
            return [];
        }

        return ['Named arguments are allowed only within native attributes'];
    }

}
