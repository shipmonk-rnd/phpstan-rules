<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function strpos;

/**
 * @implements Rule<MethodCall>
 */
class EnforceErrorIdentifierPrefixRule implements Rule
{

    private const PREFIX = 'shipmonk.';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'identifier') {
            return [];
        }

        if ($scope->getType($node->var)->getObjectClassNames() !== [RuleErrorBuilder::class]) {
            return [];
        }

        if (count($node->getArgs()) !== 1) {
            return [];
        }

        $argumentValue = $node->getArgs()[0]->value;
        $argumentStringTypes = $scope->getType($argumentValue)->getConstantStrings();

        if ($argumentStringTypes === []) {
            return [];
        }

        $errors = [];

        foreach ($argumentStringTypes as $argumentStringType) {
            if (strpos($argumentStringType->getValue(), self::PREFIX) !== 0) {
                $errors[] = RuleErrorBuilder::message("Error identifier '{$argumentStringType->getValue()}' is not prefixed with '" . self::PREFIX . "'")
                    ->identifier('shipmonk.errorIdentifierNotPrefixed')
                    ->build();
            }
        }

        return $errors;
    }

}
