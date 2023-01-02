<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<Encapsed>
 */
class ForbidNullInInterpolatedStringRule implements Rule
{

    private Standard $printer;

    public function __construct(Standard $printer)
    {
        $this->printer = $printer;
    }

    public function getNodeType(): string
    {
        return Encapsed::class;
    }

    /**
     * @param Encapsed $node
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->parts as $part) {
            if ($part instanceof EncapsedStringPart) {
                continue;
            }

            if (TypeCombinator::containsNull($scope->getType($part))) {
                $errors[] = 'Null value involved in string interpolation with ' . $this->printer->prettyPrintExpr($part);
            }
        }

        return $errors;
    }

}
