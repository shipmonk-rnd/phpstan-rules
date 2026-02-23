<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class ForbidIncrementDecrementOnNonIntegerRule implements Rule
{

    private PhpVersion $phpVersion;

    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if (
            $node instanceof PostInc
            || $node instanceof PostDec
            || $node instanceof PreInc
            || $node instanceof PreDec
        ) {
            return $this->process($node, $scope);
        }

        return [];
    }

    /**
     * @param PostInc|PostDec|PreInc|PreDec $node
     * @return list<IdentifierRuleError>
     */
    private function process(
        Node $node,
        Scope $scope
    ): array
    {
        $exprType = $scope->getType($node->var);

        if (
            $this->phpVersion->supportsBcMathNumberOperatorOverloading()
            && (new ObjectType('BcMath\Number'))->isSuperTypeOf($exprType)->yes()
        ) {
            return [];
        }

        if (!$exprType->isInteger()->yes()) {
            $errorMessage = sprintf(
                'Using %s over non-integer (%s)',
                $this->getIncDecSymbol($node),
                $exprType->describe(VerbosityLevel::typeOnly()),
            );
            $error = RuleErrorBuilder::message($errorMessage)
                ->identifier('shipmonk.incrementDecrementOnNonInteger')
                ->build();
            return [$error];
        }

        return [];
    }

    /**
     * @param PostInc|PostDec|PreInc|PreDec $node
     */
    private function getIncDecSymbol(Node $node): string
    {
        switch (get_class($node)) {
            case PostInc::class:
            case PreInc::class:
                return '++';

            case PostDec::class:
            case PreDec::class:
                return '--';

            default:
                throw new LogicException('Unexpected node given: ' . get_class($node));
        }
    }

}
