<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Match_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use ShipMonk\PHPStan\Visitor\UnusedMatchVisitor;

/**
 * @implements Rule<Match_>
 */
class ForbidUnusedMatchResultRule implements Rule
{

    public function getNodeType(): string
    {
        return Match_::class;
    }

    /**
     * @param Match_ $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $returnedTypes = [];

        foreach ($node->arms as $arm) {
            $armType = $scope->getType($arm->body);

            if (!$armType instanceof VoidType && !$armType instanceof NeverType && !$arm->body instanceof Assign) {
                $returnedTypes[] = $armType;
            }
        }

        if ($returnedTypes !== [] && $node->getAttribute(UnusedMatchVisitor::MATCH_RESULT_USED) === null) {
            return ['Unused match result detected, possible returns: ' . TypeCombinator::union(...$returnedTypes)->describe(VerbosityLevel::typeOnly())];
        }

        return [];
    }

}
