<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Unset_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Unset_>
 */
class ForbidUnsetClassFieldRule implements Rule
{

    public function getNodeType(): string
    {
        return Unset_::class;
    }

    /**
     * @param Unset_ $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        foreach ($node->vars as $item) {
            if ($item instanceof PropertyFetch) {
                return ['Unsetting class field is forbidden as it causes un-initialization, assign null instead']; // https://3v4l.org/V8uuP
            }
        }

        return [];
    }

}
