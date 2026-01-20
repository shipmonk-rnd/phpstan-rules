<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
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
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FileNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function array_map;
use function array_merge;
use function count;
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
     * class::method => callable argument index
     * or
     * function => callable argument index
     *
     * @var array<string, list<int>>
     */
    private array $callablesAllowingCheckedExceptions;

    /**
     * Tracks IIFEs (immediately invoked function expressions) like (function() { ... })()
     * These need state-based tracking because the scope doesn't have call stack info for them.
     *
     * @var array<string, true> spl_hash => true
     */
    private array $immediatelyInvokedCallables = [];

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
        // Reset IIFE tracking at the start of each file
        if ($node instanceof FileNode) {
            $this->immediatelyInvokedCallables = [];
            return [];
        }

        // Detect IIFEs: (function() { ... })() or (fn() => ...)() or ($callable)()
        // These need special tracking because the scope doesn't have call stack info for them.
        if ($node instanceof FuncCall && ($this->isClosureOrArrowFunction($node->name) || $this->isFirstClassCallable($node->name))) {
            $this->immediatelyInvokedCallables[spl_object_hash($node->name)] = true;
            return [];
        }

        if (
            $node instanceof MethodCallableNode
            || $node instanceof StaticMethodCallableNode
            || $node instanceof FunctionCallableNode
        ) {
            return $this->processFirstClassCallable($node->getOriginalNode(), $scope);
        }

        if ($node instanceof ClosureReturnStatementsNode) {
            return $this->processClosure($node, $scope);
        }

        if ($node instanceof ArrowFunction) {
            return $this->processArrowFunction($node, $scope);
        }

        return [];
    }

    private function isClosureOrArrowFunction(Node $node): bool
    {
        return $node instanceof Closure
            || $node instanceof ArrowFunction;
    }

    private function isFirstClassCallable(Node $node): bool
    {
        return ($node instanceof MethodCall && $node->isFirstClassCallable())
            || ($node instanceof NullsafeMethodCall && $node->isFirstClassCallable())
            || ($node instanceof StaticCall && $node->isFirstClassCallable())
            || ($node instanceof FuncCall && $node->isFirstClassCallable());
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

        // Check if this first-class callable is immediately invoked
        $callableHash = spl_object_hash($callNode);
        if (isset($this->immediatelyInvokedCallables[$callableHash])) {
            return [];
        }

        $callStackInfo = $this->getImmediatelyInvokedCallableInfo($scope);

        if ($callStackInfo['isImmediatelyInvoked'] || $callStackInfo['isAllowedByConfig']) {
            return [];
        }

        $errors = [];
        $line = $callNode->getStartLine();

        if ($callNode instanceof MethodCall && $callNode->name instanceof Identifier) {
            $callerType = $scope->getType($callNode->var);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName, $line, $callStackInfo['methodReference']));
        }

        if ($callNode instanceof StaticCall && $callNode->class instanceof Name && $callNode->name instanceof Identifier) {
            $callerType = $scope->resolveTypeByName($callNode->class);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName, $line, $callStackInfo['methodReference']));
        }

        if ($callNode instanceof FuncCall && $callNode->name instanceof Name && $this->reflectionProvider->hasFunction($callNode->name, $scope)) {
            $functionReflection = $this->reflectionProvider->getFunction($callNode->name, $scope);
            $errors = array_merge($errors, $this->processThrowType($functionReflection->getThrowType(), $scope, $line, $callStackInfo['methodReference']));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processClosure(
        ClosureReturnStatementsNode $node,
        Scope $scope
    ): array
    {
        // Check if this is an IIFE (immediately invoked function expression)
        $closureHash = spl_object_hash($node->getClosureExpr());
        if (isset($this->immediatelyInvokedCallables[$closureHash])) {
            return [];
        }

        $callStackInfo = $this->getImmediatelyInvokedCallableInfo($scope);

        if ($callStackInfo['isImmediatelyInvoked'] || $callStackInfo['isAllowedByConfig']) {
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
                        $callStackInfo['methodReference'],
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

        // Check if this is an IIFE (immediately invoked arrow function)
        $arrowFunctionHash = spl_object_hash($node);
        if (isset($this->immediatelyInvokedCallables[$arrowFunctionHash])) {
            return [];
        }

        $callStackInfo = $this->getImmediatelyInvokedCallableInfo($scope);

        if ($callStackInfo['isImmediatelyInvoked'] || $callStackInfo['isAllowedByConfig']) {
            return [];
        }

        $result = $this->nodeScopeResolver->processStmtNodes(
            new Namespace_(null),
            [new Expression($node->expr)],
            $scope->enterArrowFunction($node, null),
            static function (): void {
            },
            StatementContext::createTopLevel(),
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
                        $callStackInfo['methodReference'],
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
        ?string $methodReference
    ): array
    {
        $methodReflection = $scope->getMethodReflection($callerType, $methodName);

        if ($methodReflection !== null) {
            return $this->processThrowType($methodReflection->getThrowType(), $scope, $line, $methodReference);
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
        ?string $methodReference
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
                    $methodReference,
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

    /**
     * Uses Scope::getFunctionCallStackWithParameters() to check if we're inside
     * a callable parameter that is immediately invoked or allowed by config.
     *
     * @return array{isImmediatelyInvoked: bool, isAllowedByConfig: bool, methodReference: ?string}
     */
    private function getImmediatelyInvokedCallableInfo(Scope $scope): array
    {
        $callStack = $scope->getFunctionCallStackWithParameters();

        if ($callStack === []) {
            return [
                'isImmediatelyInvoked' => false,
                'isAllowedByConfig' => false,
                'methodReference' => null,
            ];
        }

        // Check the innermost call in the stack (last entry)
        [$reflection, $parameter] = $callStack[count($callStack) - 1];

        if ($parameter === null) {
            // No parameter info available, cannot determine if immediately invoked
            return [
                'isImmediatelyInvoked' => false,
                'isAllowedByConfig' => false,
                'methodReference' => null,
            ];
        }

        $isImmediatelyInvoked = $this->isImmediatelyInvokedCallable($reflection, $parameter);

        // Build method reference for error message
        $methodReference = null;
        if ($reflection instanceof MethodReflection) {
            $methodReference = $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName();
        } elseif ($reflection instanceof FunctionReflection) {
            $methodReference = $reflection->getName();
        }

        // Check if allowed by config - we need to find the parameter index
        $isAllowedByConfig = false;
        $parameterIndex = $this->findParameterIndex($reflection, $parameter);
        if ($parameterIndex !== null) {
            $callerType = $reflection instanceof MethodReflection
                ? $scope->resolveTypeByName(new Name($reflection->getDeclaringClass()->getName()))
                : null;
            $isAllowedByConfig = $this->isAllowedCheckedExceptionCallable(
                $callerType,
                $reflection->getName(),
                $parameterIndex,
            );
        }

        return [
            'isImmediatelyInvoked' => $isImmediatelyInvoked,
            'isAllowedByConfig' => $isAllowedByConfig,
            'methodReference' => $methodReference,
        ];
    }

    /**
     * @param FunctionReflection|MethodReflection $reflection
     */
    private function findParameterIndex(
        object $reflection,
        ParameterReflection $parameter
    ): ?int
    {
        foreach ($reflection->getVariants() as $variant) {
            foreach ($variant->getParameters() as $index => $param) {
                if ($param->getName() === $parameter->getName()) {
                    return $index;
                }
            }
        }

        return null;
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

}
