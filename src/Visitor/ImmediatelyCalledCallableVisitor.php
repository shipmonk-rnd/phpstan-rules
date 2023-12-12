<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use function array_merge;
use function array_merge_recursive;
use function array_unique;
use function array_values;
use function explode;
use function is_int;
use function strpos;

class ImmediatelyCalledCallableVisitor extends NodeVisitorAbstract
{

    public const CALLABLE_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'callableAllowingCheckedException';
    public const CALLER_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'callerWithCallablePossiblyAllowingCheckedException';
    public const METHOD_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'methodWithCallablePossiblyAllowingCheckedException';
    public const ARGUMENT_INDEX_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'argumentIndexWithCallablePossiblyAllowingCheckedException';

    /**
     * method name => callable argument indexes
     *
     * @var array<string, list<int>>
     */
    private array $methodsWithAllowedCheckedExceptions = [];

    /**
     * function name => callable argument indexes
     *
     * @var array<string, list<int>>
     */
    private array $functionsWithAllowedCheckedExceptions = [];

    /**
     * @param array<string, int|list<int>> $immediatelyCalledCallables
     * @param array<string, int|list<int>> $allowedCheckedExceptionCallables
     */
    public function __construct(
        array $immediatelyCalledCallables = [],
        array $allowedCheckedExceptionCallables = []
    )
    {
        /** @var array<string, int|list<int>> $callablesWithAllowedCheckedExceptions */
        $callablesWithAllowedCheckedExceptions = array_merge_recursive($immediatelyCalledCallables, $allowedCheckedExceptionCallables);

        foreach ($callablesWithAllowedCheckedExceptions as $call => $arguments) {
            $normalizedArguments = $this->normalizeArgumentIndexes($arguments);

            if (strpos($call, '::') !== false) {
                [, $methodName] = explode('::', $call);
                $existingArguments = $this->methodsWithAllowedCheckedExceptions[$methodName] ?? [];
                $this->methodsWithAllowedCheckedExceptions[$methodName] = array_values(array_unique(array_merge($existingArguments, $normalizedArguments)));
            } else {
                $this->functionsWithAllowedCheckedExceptions[$call] = $normalizedArguments;
            }
        }
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall) {
            $this->resolveMethodCall($node);
        }

        if ($node instanceof FuncCall) {
            $this->resolveFuncCall($node);
        }

        return null;
    }

    /**
     * @param StaticCall|MethodCall|NullsafeMethodCall $node
     */
    private function resolveMethodCall(CallLike $node): void
    {
        if (!$node->name instanceof Identifier) {
            return;
        }

        $methodName = $node->name->name;
        $argumentIndexes = $this->methodsWithAllowedCheckedExceptions[$methodName] ?? null;

        if ($argumentIndexes === null) {
            return;
        }

        foreach ($argumentIndexes as $argumentIndex) {
            $argument = $node->getArgs()[$argumentIndex] ?? null;

            if ($argument === null) {
                continue;
            }

            if (!$this->isFirstClassCallableOrClosureOrArrowFunction($argument->value)) {
                continue;
            }

            // we cannot decide true/false like in function calls as we dont know caller type yet, this has to be resolved in Rule
            $node->getArgs()[$argumentIndex]->value->setAttribute(self::CALLER_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION, $node instanceof StaticCall ? $node->class : $node->var);
            $node->getArgs()[$argumentIndex]->value->setAttribute(self::METHOD_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION, $node->name->toString());
            $node->getArgs()[$argumentIndex]->value->setAttribute(self::ARGUMENT_INDEX_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION, $argumentIndex);
        }
    }

    private function resolveFuncCall(FuncCall $node): void
    {
        if ($this->isFirstClassCallableOrClosureOrArrowFunction($node->name)) {
            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            $node->name->setAttribute(self::CALLABLE_ALLOWING_CHECKED_EXCEPTION, true); // immediately called closure syntax, e.g. (function(){})()
            return;
        }

        if (!$node->name instanceof Name) {
            return;
        }

        $methodName = $node->name->toString();
        $argumentIndexes = $this->functionsWithAllowedCheckedExceptions[$methodName] ?? null;

        if ($argumentIndexes === null) {
            return;
        }

        foreach ($argumentIndexes as $argumentIndex) {
            $argument = $node->getArgs()[$argumentIndex] ?? null;

            if ($argument === null) {
                continue;
            }

            if (!$this->isFirstClassCallableOrClosureOrArrowFunction($argument->value)) {
                continue;
            }

            $node->getArgs()[$argumentIndex]->value->setAttribute(self::CALLABLE_ALLOWING_CHECKED_EXCEPTION, true);
        }
    }

    private function isFirstClassCallableOrClosureOrArrowFunction(Node $node): bool
    {
        return $node instanceof Closure
            || $node instanceof ArrowFunction
            || ($node instanceof MethodCall && $node->isFirstClassCallable())
            || ($node instanceof NullsafeMethodCall && $node->isFirstClassCallable())
            || ($node instanceof StaticCall && $node->isFirstClassCallable())
            || ($node instanceof FuncCall && $node->isFirstClassCallable());
    }

    /**
     * @param int|list<int> $argumentIndexes
     * @return list<int>
     */
    private function normalizeArgumentIndexes($argumentIndexes): array
    {
        return is_int($argumentIndexes) ? [$argumentIndexes] : $argumentIndexes;
    }

}
