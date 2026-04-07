<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FileNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use ShipMonk\PHPStan\Helper\ImmediatelyInvokedCallableHelper;
use function array_map;
use function array_shift;
use function explode;
use function in_array;
use function is_int;
use function spl_object_hash;
use function strpos;

/**
 * @implements Rule<Node>
 */
class ForbidCheckedExceptionInCallableRule implements Rule
{

    private NodeScopeResolver $nodeScopeResolver;

    private ReflectionProvider $reflectionProvider;

    private DefaultExceptionTypeResolver $exceptionTypeResolver;

    private ImmediatelyInvokedCallableHelper $callableHelper;

    /**
     * @var array<string, bool> spl_hash => true
     */
    private array $allowedCallables = [];

    /**
     * @var array<string, string> spl_hash => methodName
     */
    private array $callablesInArguments = [];

    /**
     * class::method => callable argument index
     * or
     * function => callable argument index
     *
     * @var array<string, list<int>>
     */
    private array $callablesAllowingCheckedExceptions;

    /**
     * PHPStan's fiber-based processing no longer guarantees that CallLike nodes are processed before their Arg nodes.
     * This caused false positives when callables (e.g. closures) were processed before their parent CallLike, seeing empty allowedCallables and incorrectly reporting errors.
     * So we delay processing of those until the end of the file.
     *
     * @var list<Generator<IdentifierRuleError>>
     */
    private array $pending = [];

    /**
     * @param array<string, int|list<int>> $allowedCheckedExceptionCallables
     */
    public function __construct(
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        DefaultExceptionTypeResolver $exceptionTypeResolver,
        array $allowedCheckedExceptionCallables,
        ImmediatelyInvokedCallableHelper $callableHelper
    )
    {
        $this->checkClassExistence($reflectionProvider, $allowedCheckedExceptionCallables);

        $this->callablesAllowingCheckedExceptions = array_map(
            function ($argumentIndexes): array {
                return $this->normalizeArgumentIndexes($argumentIndexes);
            },
            $allowedCheckedExceptionCallables,
        );
        $this->exceptionTypeResolver = $exceptionTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->callableHelper = $callableHelper;
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
        $errors = [];

        if ($node instanceof FileNode) {
            $this->pending = [];
            $this->allowedCallables = [];
            $this->callablesInArguments = [];

        } elseif ($node instanceof CallLike) {
            $this->whitelistAllowedCallables($node, $scope);

        } elseif (
            $node instanceof MethodCallableNode
            || $node instanceof StaticMethodCallableNode
            || $node instanceof FunctionCallableNode
        ) {
            $this->pending[] = $this->processFirstClassCallable($node->getOriginalNode(), $scope);

        } elseif ($node instanceof ClosureReturnStatementsNode) {
            $this->pending[] = $this->processClosure($node);

        } elseif ($node instanceof ArrowFunction) {
            $this->pending[] = $this->processArrowFunction($node, $scope);
        }

        if (!$scope->isInClass() || $node instanceof ClassMethodsNode) {
            while ($this->pending !== []) {
                $generator = array_shift($this->pending);
                foreach ($generator as $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * @param MethodCall|StaticCall|FuncCall $callNode
     * @return Generator<IdentifierRuleError>
     */
    public function processFirstClassCallable(
        CallLike $callNode,
        Scope $scope
    ): Generator
    {
        if (!$callNode->isFirstClassCallable()) {
            throw new LogicException('This should be ensured by using XxxCallableNode');
        }

        $nodeHash = spl_object_hash($callNode);

        if (isset($this->allowedCallables[$nodeHash])) {
            return;
        }

        $line = $callNode->getStartLine();

        if ($callNode instanceof MethodCall && $callNode->name instanceof Identifier) {
            $callerType = $scope->getType($callNode->var);
            $methodName = $callNode->name->toString();

            yield from $this->processCall($scope, $callerType, $methodName, $line, $nodeHash);
        }

        if ($callNode instanceof StaticCall && $callNode->class instanceof Name && $callNode->name instanceof Identifier) {
            $callerType = $scope->resolveTypeByName($callNode->class);
            $methodName = $callNode->name->toString();

            yield from $this->processCall($scope, $callerType, $methodName, $line, $nodeHash);
        }

        if ($callNode instanceof FuncCall && $callNode->name instanceof Name && $this->reflectionProvider->hasFunction($callNode->name, $scope)) {
            $functionReflection = $this->reflectionProvider->getFunction($callNode->name, $scope);
            yield from $this->processThrowType($functionReflection->getThrowType(), $scope, $line, $nodeHash);
        }
    }

    /**
     * @return Generator<IdentifierRuleError>
     */
    public function processClosure(
        ClosureReturnStatementsNode $node
    ): Generator
    {
        $nodeHash = spl_object_hash($node->getClosureExpr());

        if (isset($this->allowedCallables[$nodeHash])) {
            return;
        }

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    yield $this->buildError(
                        $exceptionClass,
                        'closure',
                        $throwPoint->getScope(),
                        $throwPoint->getNode()->getStartLine(),
                        $this->callablesInArguments[$nodeHash] ?? null,
                    );
                }
            }
        }
    }

    /**
     * @return Generator<IdentifierRuleError>
     */
    public function processArrowFunction(
        ArrowFunction $node,
        Scope $scope
    ): Generator
    {
        if (!$scope instanceof MutatingScope) {
            throw new LogicException('Unexpected scope implementation');
        }

        $nodeHash = spl_object_hash($node);

        if (isset($this->allowedCallables[$nodeHash])) {
            return;
        }

        $result = $this->nodeScopeResolver->processStmtNodes(
            new Namespace_(null),
            [new Expression($node->expr)],
            $scope->enterArrowFunction($node, null),
            static function (): void {
            },
            StatementContext::createTopLevel(),
        );

        foreach ($result->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    yield $this->buildError(
                        $exceptionClass,
                        'arrow function',
                        $throwPoint->getScope(),
                        $throwPoint->getNode()->getStartLine(),
                        $this->callablesInArguments[$nodeHash] ?? null,
                    );
                }
            }
        }
    }

    /**
     * @return Generator<IdentifierRuleError>
     */
    private function processCall(
        Scope $scope,
        Type $callerType,
        string $methodName,
        int $line,
        string $nodeHash
    ): Generator
    {
        $methodReflection = $scope->getMethodReflection($callerType, $methodName);

        if ($methodReflection !== null) {
            yield from $this->processThrowType($methodReflection->getThrowType(), $scope, $line, $nodeHash);
        }
    }

    /**
     * @return Generator<IdentifierRuleError>
     */
    private function processThrowType(
        ?Type $throwType,
        Scope $scope,
        int $line,
        string $nodeHash
    ): Generator
    {
        if ($throwType === null) {
            return;
        }

        foreach ($throwType->getObjectClassNames() as $exceptionClass) {
            if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $scope)) {
                yield $this->buildError(
                    $exceptionClass,
                    'first-class-callable',
                    $scope,
                    $line,
                    $this->callablesInArguments[$nodeHash] ?? null,
                );
            }
        }
    }

    /**
     * @param int|list<int> $argumentIndexes
     * @return list<int>
     */
    private function normalizeArgumentIndexes($argumentIndexes): array
    {
        return is_int($argumentIndexes) ? [$argumentIndexes] : $argumentIndexes;
    }

    /**
     * @param array<string, int|list<int>> $callables
     */
    private function checkClassExistence(
        ReflectionProvider $reflectionProvider,
        array $callables
    ): void
    {
        foreach ($callables as $call => $args) {
            if (strpos($call, '::') === false) {
                continue;
            }

            [$className] = explode('::', $call);

            if (!$reflectionProvider->hasClass($className)) {
                throw new LogicException("Class $className used in 'allowedCheckedExceptionCallables' does not exist.");
            }
        }
    }

    private function isAllowedCheckedExceptionCallable(
        ?Type $caller,
        string $calledMethodName,
        int $argumentIndex
    ): bool
    {
        if ($caller === null) {
            foreach ($this->callablesAllowingCheckedExceptions as $immediateFunction => $indexes) {
                if (strpos($immediateFunction, '::') !== false) {
                    continue;
                }

                if (
                    $immediateFunction === $calledMethodName
                    && in_array($argumentIndex, $indexes, true)
                ) {
                    return true;
                }
            }

            return false;
        }

        foreach ($caller->getObjectClassReflections() as $callerReflection) {
            foreach ($this->callablesAllowingCheckedExceptions as $immediateCallerAndMethod => $indexes) {
                if (strpos($immediateCallerAndMethod, '::') === false) {
                    continue;
                }

                [$callerClass, $methodName] = explode('::', $immediateCallerAndMethod); // @phpstan-ignore offsetAccess.notFound

                if (
                    $methodName === $calledMethodName
                    && in_array($argumentIndex, $indexes, true)
                    && $callerReflection->is($callerClass)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function whitelistAllowedCallables(
        CallLike $node,
        Scope $scope
    ): void
    {
        // Directly invoked callable syntax: (function(){...})(), (fn() => ...)()
        if ($node instanceof FuncCall && $this->callableHelper->isCallableExpression($node->name)) {
            $this->allowedCallables[spl_object_hash($node->name)] = true;
            return;
        }

        $analysis = $this->callableHelper->analyzeCall($node, $scope);

        if ($analysis === null) {
            return;
        }

        foreach ($analysis->reorderedArguments as $index => $arg) {
            $argHash = spl_object_hash($arg->value);

            if (
                isset($analysis->immediatelyInvokedHashes[$argHash])
                || $this->isAllowedCheckedExceptionCallable($analysis->callerType, $analysis->reflection->getName(), $index)
            ) {
                $this->allowedCallables[$argHash] = true;
            }

            if ($this->callableHelper->isCallableExpression($arg->value)) {
                $callerClass = $analysis->callerType !== null && $analysis->callerType->getObjectClassNames() !== [] ? $analysis->callerType->getObjectClassNames()[0] : null;
                $methodReference = $callerClass !== null ? "$callerClass::{$analysis->reflection->getName()}" : $analysis->reflection->getName();
                $this->callablesInArguments[$argHash] = $methodReference;
            }
        }
    }

    private function buildError(
        string $exceptionClass,
        string $where,
        Scope $scope,
        int $line,
        ?string $usedAsArgumentOfMethodName
    ): IdentifierRuleError
    {
        $file = $scope->getFile();
        $fileDescription = $scope->getFileDescription();

        $builder = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in $where!")
            ->file($file, $fileDescription)
            ->line($line)
            ->identifier('shipmonk.checkedExceptionInCallable');

        if ($usedAsArgumentOfMethodName !== null) {
            $builder->tip("If this callable is immediately called within '$usedAsArgumentOfMethodName', you should add @param-immediately-invoked-callable there. Then this error disappears and the exception will be properly propagated.");
        }

        return $builder->build();
    }

}
