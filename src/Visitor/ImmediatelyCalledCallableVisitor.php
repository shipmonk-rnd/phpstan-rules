<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use function array_merge_recursive;
use function explode;
use function is_int;
use function strpos;

class ImmediatelyCalledCallableVisitor extends NodeVisitorAbstract
{

    public const CALLABLE_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'callableAllowingCheckedException';
    public const CALLER_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'callerWithCallablePossiblyAllowingCheckedException';
    public const METHOD_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION = ShipMonkNodeVisitor::NODE_ATTRIBUTE_PREFIX . 'methodWithCallablePossiblyAllowingCheckedException';

    /**
     * method name => callable argument indexes
     *
     * @var array<string, int|list<int>>
     */
    private array $methodsWithAllowedCheckedExceptions = [];

    /**
     * function name => callable argument indexes
     *
     * @var array<string, int|list<int>>
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
        $callablesWithAllowedCheckedExceptions = array_merge_recursive($immediatelyCalledCallables, $allowedCheckedExceptionCallables);

        foreach ($callablesWithAllowedCheckedExceptions as $call => $arguments) {
            if (strpos($call, '::') !== false) {
                [, $methodName] = explode('::', $call);
                $this->methodsWithAllowedCheckedExceptions[$methodName] = $arguments;
            } else {
                $this->functionsWithAllowedCheckedExceptions[$call] = $arguments;
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

        foreach ($this->normalizeArgumentIndexes($argumentIndexes) as $argumentIndex) {
            $argument = $node->getArgs()[$argumentIndex] ?? null;

            if ($argument === null) {
                continue;
            }

            if (!$this->isFirstClassCallableOrClosure($argument->value)) {
                continue;
            }

            // we cannot decide true/false like in function calls as we dont know caller type yet, this has to be resolved in Rule
            $node->getArgs()[$argumentIndex]->value->setAttribute(self::CALLER_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION, $node instanceof StaticCall ? $node->class : $node->var);
            $node->getArgs()[$argumentIndex]->value->setAttribute(self::METHOD_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION, $node->name->toString());
        }
    }

    private function resolveFuncCall(FuncCall $node): void
    {
        if ($this->isFirstClassCallableOrClosure($node->name)) {
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

        foreach ($this->normalizeArgumentIndexes($argumentIndexes) as $argumentIndex) {
            $argument = $node->getArgs()[$argumentIndex] ?? null;

            if ($argument === null) {
                continue;
            }

            if (!$this->isFirstClassCallableOrClosure($argument->value)) {
                continue;
            }

            $node->getArgs()[$argumentIndex]->value->setAttribute(self::CALLABLE_ALLOWING_CHECKED_EXCEPTION, true);
        }
    }

    private function isFirstClassCallableOrClosure(Node $node): bool
    {
        return $node instanceof Closure
            || ($node instanceof MethodCall && $node->isFirstClassCallable())
            || ($node instanceof NullsafeMethodCall && $node->isFirstClassCallable())
            || ($node instanceof StaticCall && $node->isFirstClassCallable())
            || ($node instanceof FuncCall && $node->isFirstClassCallable());
    }

    /**
     * @param int|list<int> $argumentIndexes
     * @return list<int>
     */
    private function normalizeArgumentIndexes(int|array $argumentIndexes): array
    {
        return is_int($argumentIndexes) ? [$argumentIndexes] : $argumentIndexes;
    }

}
