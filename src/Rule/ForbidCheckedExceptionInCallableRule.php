<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FileNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function array_map;
use function array_merge;
use function array_values;
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
     * @param array<string, int|list<int>> $allowedCheckedExceptionCallables
     */
    public function __construct(
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        DefaultExceptionTypeResolver $exceptionTypeResolver,
        array $allowedCheckedExceptionCallables
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
            $this->allowedCallables = [];
            $this->callablesInArguments = [];

        } elseif ($node instanceof CallLike) {
            $this->whitelistAllowedCallables($node, $scope);

        } elseif (
            $node instanceof MethodCallableNode
            || $node instanceof StaticMethodCallableNode
            || $node instanceof FunctionCallableNode
        ) {
            return $this->processFirstClassCallable($node->getOriginalNode(), $scope);

        } elseif ($node instanceof ClosureReturnStatementsNode) {
            return $this->processClosure($node);

        } elseif ($node instanceof ArrowFunction) {
            return $this->processArrowFunction($node, $scope);
        }

        return $errors;
    }

    /**
     * @param MethodCall|StaticCall|FuncCall $callNode
     * @return list<IdentifierRuleError>
     */
    public function processFirstClassCallable(
        CallLike $callNode,
        Scope $scope
    ): array
    {
        if (!$callNode->isFirstClassCallable()) {
            throw new LogicException('This should be ensured by using XxxCallableNode');
        }

        $nodeHash = spl_object_hash($callNode);

        if (isset($this->allowedCallables[$nodeHash])) {
            return [];
        }

        $errors = [];
        $line = $callNode->getStartLine();

        if ($callNode instanceof MethodCall && $callNode->name instanceof Identifier) {
            $callerType = $scope->getType($callNode->var);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName, $line, $nodeHash));
        }

        if ($callNode instanceof StaticCall && $callNode->class instanceof Name && $callNode->name instanceof Identifier) {
            $callerType = $scope->resolveTypeByName($callNode->class);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName, $line, $nodeHash));
        }

        if ($callNode instanceof FuncCall && $callNode->name instanceof Name && $this->reflectionProvider->hasFunction($callNode->name, $scope)) {
            $functionReflection = $this->reflectionProvider->getFunction($callNode->name, $scope);
            $errors = array_merge($errors, $this->processThrowType($functionReflection->getThrowType(), $scope, $line, $nodeHash));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processClosure(
        ClosureReturnStatementsNode $node
    ): array
    {
        $nodeHash = spl_object_hash($node->getClosureExpr());

        if (isset($this->allowedCallables[$nodeHash])) {
            return [];
        }

        $errors = [];

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = $this->buildError(
                        $exceptionClass,
                        'closure',
                        $throwPoint->getNode()->getStartLine(),
                        $this->callablesInArguments[$nodeHash] ?? null,
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processArrowFunction(
        ArrowFunction $node,
        Scope $scope
    ): array
    {
        if (!$scope instanceof MutatingScope) {
            throw new LogicException('Unexpected scope implementation');
        }

        $nodeHash = spl_object_hash($node);

        if (isset($this->allowedCallables[$nodeHash])) {
            return [];
        }

        $result = $this->nodeScopeResolver->processExprNode(
            new Expression($node->expr),
            $node->expr,
            $scope->enterArrowFunction($node, null),
            static function (): void {
            },
            ExpressionContext::createDeep(),
        );

        $errors = [];

        foreach ($result->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = $this->buildError(
                        $exceptionClass,
                        'arrow function',
                        $throwPoint->getNode()->getStartLine(),
                        $this->callablesInArguments[$nodeHash] ?? null,
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processCall(
        Scope $scope,
        Type $callerType,
        string $methodName,
        int $line,
        string $nodeHash
    ): array
    {
        $methodReflection = $scope->getMethodReflection($callerType, $methodName);

        if ($methodReflection !== null) {
            return $this->processThrowType($methodReflection->getThrowType(), $scope, $line, $nodeHash);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processThrowType(
        ?Type $throwType,
        Scope $scope,
        int $line,
        string $nodeHash
    ): array
    {
        if ($throwType === null) {
            return [];
        }

        $errors = [];

        foreach ($throwType->getObjectClassNames() as $exceptionClass) {
            if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $scope)) {
                $errors[] = $this->buildError(
                    $exceptionClass,
                    'first-class-callable',
                    $line,
                    $this->callablesInArguments[$nodeHash] ?? null,
                );
            }
        }

        return $errors;
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

    /**
     * Copied from phpstan https://github.com/phpstan/phpstan-src/commit/cefa296f24b8c0b7d4dc3d383cbceea35267cb3f#diff-0c3f50d118357d9cb6d6f4d0eade75b83797d57056ff3b9c58ec881a13eaa6feR4113
     *
     * @param FunctionReflection|MethodReflection $reflection
     */
    private function isImmediatelyInvokedCallable(
        object $reflection,
        ?ParameterReflection $parameter
    ): bool
    {
        if ($parameter instanceof ExtendedParameterReflection) {
            $parameterCallImmediately = $parameter->isImmediatelyInvokedCallable();

            if ($parameterCallImmediately->maybe()) {
                return $reflection instanceof FunctionReflection;
            }

            return $parameterCallImmediately->yes();
        }

        return $reflection instanceof FunctionReflection;
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
        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $callerType = $scope->getType($node->var);
            $methodReflection = $scope->getMethodReflection($callerType, $node->name->name);

        } elseif ($node instanceof StaticCall && $node->name instanceof Identifier && $node->class instanceof Name) {
            $callerType = $scope->resolveTypeByName($node->class);
            $methodReflection = $scope->getMethodReflection($callerType, $node->name->name);

        } elseif ($node instanceof New_ && $node->class instanceof Name) {
            $callerType = $scope->resolveTypeByName($node->class);
            $methodReflection = $scope->getMethodReflection($callerType, '__construct');

        } elseif ($node instanceof FuncCall && $node->name instanceof Name) {
            $callerType = null;
            $methodReflection = $this->getFunctionReflection($node->name, $scope);

        } elseif ($node instanceof FuncCall && $this->isFirstClassCallableOrClosureOrArrowFunction($node->name)) { // immediately called callable syntax
            $this->allowedCallables[spl_object_hash($node->name)] = true;
            return;

        } else {
            return;
        }

        if ($methodReflection === null) {
            return;
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $node->getArgs(),
            $methodReflection->getVariants(),
            $methodReflection->getNamedArgumentsVariants(),
        );

        if ($node instanceof New_) {
            $arguments = (ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $node) ?? $node)->getArgs();

        } elseif ($node instanceof FuncCall) {
            $arguments = (ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node) ?? $node)->getArgs();

        } elseif ($node instanceof MethodCall) {
            $arguments = (ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $node) ?? $node)->getArgs();

        } elseif ($node instanceof StaticCall) {
            $arguments = (ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $node) ?? $node)->getArgs();

        } else {
            throw new LogicException('Unexpected node type');
        }

        /** @var list<Arg> $args */
        $args = array_values($arguments);
        $parameters = $parametersAcceptor->getParameters();

        foreach ($args as $index => $arg) {
            $parameterIndex = $this->getParameterIndex($arg, $index, $parameters) ?? -1;
            $parameter = $parameters[$parameterIndex] ?? null;
            $argHash = spl_object_hash($arg->value);

            if (
                $this->isImmediatelyInvokedCallable($methodReflection, $parameter)
                || $this->isAllowedCheckedExceptionCallable($callerType, $methodReflection->getName(), $index)
            ) {
                $this->allowedCallables[$argHash] = true;
            }

            if ($this->isFirstClassCallableOrClosureOrArrowFunction($arg->value)) {
                $callerClass = $callerType !== null && $callerType->getObjectClassNames() !== [] ? $callerType->getObjectClassNames()[0] : null;
                $methodReference = $callerClass !== null ? "$callerClass::{$methodReflection->getName()}" : $methodReflection->getName();
                $this->callablesInArguments[$argHash] = $methodReference;
            }
        }
    }

    /**
     * @param array<int, ParameterReflection> $parameters
     */
    private function getParameterIndex(
        Arg $arg,
        int $argumentIndex,
        array $parameters
    ): ?int
    {
        if ($arg->name === null) {
            return $argumentIndex;
        }

        foreach ($parameters as $parameterIndex => $parameter) {
            if ($parameter->getName() === $arg->name->toString()) {
                return $parameterIndex;
            }
        }

        return null;
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

    private function buildError(
        string $exceptionClass,
        string $where,
        int $line,
        ?string $usedAsArgumentOfMethodName
    ): IdentifierRuleError
    {
        $builder = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in $where!")
            ->line($line)
            ->identifier('shipmonk.checkedExceptionInCallable');

        if ($usedAsArgumentOfMethodName !== null) {
            $builder->tip("If this callable is immediately called within '$usedAsArgumentOfMethodName', you should add @param-immediately-invoked-callable there. Then this error disappears and the exception will be properly propagated.");
        }

        return $builder->build();
    }

    private function getFunctionReflection(
        Name $functionName,
        Scope $scope
    ): ?FunctionReflection
    {
        return $this->reflectionProvider->hasFunction($functionName, $scope)
            ? $this->reflectionProvider->getFunction($functionName, $scope)
            : null;
    }

}
