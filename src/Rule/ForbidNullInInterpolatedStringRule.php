<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<InterpolatedString>
 */
class ForbidNullInInterpolatedStringRule implements Rule
{

    private Printer $printer;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function getNodeType(): string
    {
        return InterpolatedString::class;
    }

    /**
     * @param InterpolatedString $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $errors = [];

        foreach ($node->parts as $part) {
            if ($part instanceof InterpolatedStringPart) {
                continue;
            }

            if (TypeCombinator::containsNull($scope->getType($part))) {
                $errors[] = RuleErrorBuilder::message('Null value involved in string interpolation with ' . $this->printer->prettyPrintExpr($part))
                    ->identifier('shipmonk.stringInterpolationWithNull')
                    ->build();

            }
        }

        return $errors;
    }

}
