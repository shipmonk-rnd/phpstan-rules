<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<FuncCall>
 */
class EnforceIteratorToArrayPreserveKeysRule implements Rule
{

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        if ($node->name->toString() !== 'iterator_to_array') {
            return [];
        }

        if (count($node->getArgs()) >= 2) {
            return [];
        }

        if (count($node->getArgs()) === 0) {
            return [];
        }

        if ($node->getArgs()[0]->unpack) {
            return []; // not trying to analyse what is being unpacked as this is very non-standard approach here
        }

        return [RuleErrorBuilder::message('Calling iterator_to_array without 2nd parameter $preserve_keys. Default value true might cause failures or data loss.')
            ->line($node->getLine())
            ->identifier('shipmonk.iteratorToArrayWithoutPreserveKeys')
            ->build()];
    }

}
