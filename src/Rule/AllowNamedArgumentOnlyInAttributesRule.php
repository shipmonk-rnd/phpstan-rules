<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        if ($node->getAttribute(NamedArgumentSourceVisitor::IS_ATTRIBUTE_NAMED_ARGUMENT) === true) {
            return [];
        }

        $error = RuleErrorBuilder::message('Named arguments are allowed only within native attributes')
            ->identifier('namedArgumentOutsideAttribute')
            ->build();
        return [$error];
    }

}
