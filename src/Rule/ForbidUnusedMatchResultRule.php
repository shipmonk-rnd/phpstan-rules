<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Match_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use ShipMonk\PHPStan\Visitor\UnusedMatchVisitor;
use function method_exists;

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
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $returnedTypes = [];

        foreach ($node->arms as $arm) {
            /** @var Type $armType */
            $armType = method_exists($scope, 'getKeepVoidType') // Needed since https://github.com/phpstan/phpstan/releases/tag/1.10.49, can be dropped once we bump PHPStan version gte that
                ? $scope->getKeepVoidType($arm->body)
                : $scope->getType($arm->body);

            if (!$armType->isVoid()->yes() && !$armType instanceof NeverType && !$arm->body instanceof Assign) {
                $returnedTypes[] = $armType;
            }
        }

        if ($returnedTypes !== [] && $node->getAttribute(UnusedMatchVisitor::MATCH_RESULT_USED) === null) {
            $error = RuleErrorBuilder::message('Unused match result detected, possible returns: ' . TypeCombinator::union(...$returnedTypes)->describe(VerbosityLevel::typeOnly()))
                ->identifier('shipmonk.unusedMatchResult')
                ->build();
            return [$error];
        }

        return [];
    }

}
