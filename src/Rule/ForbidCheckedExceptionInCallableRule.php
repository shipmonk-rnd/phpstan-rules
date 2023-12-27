<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use ShipMonk\PHPStan\Visitor\ImmediatelyCalledCallableVisitor;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function explode;
use function in_array;
use function is_int;
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
     * class::method => Closure argument index
     * or
     * function => Closure argument index
     *
     * @var array<string, list<int>>
     */
    private array $callablesAllowingCheckedExceptions;

    /**
     * @param array<string, int|list<int>> $immediatelyCalledCallables
     * @param array<string, int|list<int>> $allowedCheckedExceptionCallables
     */
    public function __construct(
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        DefaultExceptionTypeResolver $exceptionTypeResolver,
        array $immediatelyCalledCallables,
        array $allowedCheckedExceptionCallables
    )
    {
        $this->checkClassExistence($reflectionProvider, $immediatelyCalledCallables, 'immediatelyCalledCallables');
        $this->checkClassExistence($reflectionProvider, $allowedCheckedExceptionCallables, 'allowedCheckedExceptionCallables');

        /** @var array<string, int|list<int>> $callablesWithAllowedCheckedExceptions */
        $callablesWithAllowedCheckedExceptions = array_merge_recursive($immediatelyCalledCallables, $allowedCheckedExceptionCallables);

        $this->callablesAllowingCheckedExceptions = array_map(
            function ($argumentIndexes): array {
                return $this->normalizeArgumentIndexes($argumentIndexes);
            },
            $callablesWithAllowedCheckedExceptions,
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
     * @return list<RuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if (
            $node instanceof MethodCallableNode // @phpstan-ignore-line ignore bc promise
            || $node instanceof StaticMethodCallableNode // @phpstan-ignore-line ignore bc promise
            || $node instanceof FunctionCallableNode // @phpstan-ignore-line ignore bc promise
        ) {
            return $this->processFirstClassCallable($node->getOriginalNode(), $scope);
        }

        if ($node instanceof ClosureReturnStatementsNode) { // @phpstan-ignore-line ignore bc promise
            return $this->processClosure($node, $scope);
        }

        if ($node instanceof ArrowFunction) {
            return $this->processArrowFunction($node, $scope);
        }

        return [];
    }

    /**
     * @param MethodCall|StaticCall|FuncCall $callNode
     * @return list<RuleError>
     */
    public function processFirstClassCallable(
        CallLike $callNode,
        Scope $scope
    ): array
    {
        if (!$callNode->isFirstClassCallable()) {
            throw new LogicException('This should be ensured by using XxxCallableNode');
        }

        if ($this->isAllowedToThrowCheckedException($callNode, $scope)) {
            return [];
        }

        $errors = [];

        if ($callNode instanceof MethodCall && $callNode->name instanceof Identifier) {
            $callerType = $scope->getType($callNode->var);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName));
        }

        if ($callNode instanceof StaticCall && $callNode->class instanceof Name && $callNode->name instanceof Identifier) {
            $callerType = $scope->resolveTypeByName($callNode->class);
            $methodName = $callNode->name->toString();

            $errors = array_merge($errors, $this->processCall($scope, $callerType, $methodName));
        }

        if ($callNode instanceof FuncCall && $callNode->name instanceof Name) {
            $functionReflection = $this->reflectionProvider->getFunction($callNode->name, $scope);
            $errors = array_merge($errors, $this->processThrowType($functionReflection->getThrowType(), $scope));
        }

        return $errors;
    }

    /**
     * @return list<RuleError>
     */
    public function processClosure(
        ClosureReturnStatementsNode $node,
        Scope $scope
    ): array
    {
        $closure = $node->getClosureExpr();
        $parentScope = $scope->getParentScope(); // we need to detect type of caller, so the scope outside of this closure is needed

        if ($parentScope === null) {
            return [];
        }

        if ($this->isAllowedToThrowCheckedException($closure, $parentScope)) {
            return [];
        }

        $errors = [];

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in closure!")
                        ->line($throwPoint->getNode()->getLine())
                        ->identifier('shipmonk.checkedExceptionInCallable')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<RuleError>
     */
    public function processArrowFunction(
        ArrowFunction $node,
        Scope $scope
    ): array
    {
        if (!$scope instanceof MutatingScope) { // @phpstan-ignore-line ignore BC promise
            throw new LogicException('Unexpected scope implementation');
        }

        if ($this->isAllowedToThrowCheckedException($node, $scope)) {
            return [];
        }

        $result = $this->nodeScopeResolver->processExprNode( // @phpstan-ignore-line ignore BC promise
            new Expression($node->expr),
            $node->expr,
            $scope->enterArrowFunction($node),
            static function (): void {
            },
            ExpressionContext::createDeep(), // @phpstan-ignore-line ignore BC promise
        );

        $errors = [];

        foreach ($result->getThrowPoints() as $throwPoint) { // @phpstan-ignore-line ignore BC promise
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in arrow function!")
                        ->line($throwPoint->getNode()->getLine())
                        ->identifier('shipmonk.checkedExceptionInArrowFunction')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<RuleError>
     */
    private function processCall(
        Scope $scope,
        Type $callerType,
        string $methodName
    ): array
    {
        $methodReflection = $scope->getMethodReflection($callerType, $methodName);

        if ($methodReflection !== null) {
            return $this->processThrowType($methodReflection->getThrowType(), $scope);
        }

        return [];
    }

    /**
     * @return list<RuleError>
     */
    private function processThrowType(
        ?Type $throwType,
        Scope $scope
    ): array
    {
        if ($throwType === null) {
            return [];
        }

        $errors = [];

        foreach ($throwType->getObjectClassNames() as $exceptionClass) {
            if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $scope)) {
                $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in first-class-callable!")
                    ->identifier('shipmonk.checkedExceptionInCallable')
                    ->build();
            }
        }

        return $errors;
    }

    public function isAllowedToThrowCheckedException(
        Node $node,
        Scope $scope
    ): bool
    {
        /** @var Expr|Name|null $callerNodeWithClosureAsArg */
        $callerNodeWithClosureAsArg = $node->getAttribute(ImmediatelyCalledCallableVisitor::CALLER_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION);
        /** @var string|null $methodNameWithClosureAsArg */
        $methodNameWithClosureAsArg = $node->getAttribute(ImmediatelyCalledCallableVisitor::METHOD_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION);
        /** @var int|null $argumentIndexWithClosureAsArg */
        $argumentIndexWithClosureAsArg = $node->getAttribute(ImmediatelyCalledCallableVisitor::ARGUMENT_INDEX_WITH_CALLABLE_POSSIBLY_ALLOWING_CHECKED_EXCEPTION);
        /** @var true|null $isAllowedToThrow */
        $isAllowedToThrow = $node->getAttribute(ImmediatelyCalledCallableVisitor::CALLABLE_ALLOWING_CHECKED_EXCEPTION);

        if ($isAllowedToThrow === true) {
            return true;
        }

        if ($callerNodeWithClosureAsArg === null || $methodNameWithClosureAsArg === null || $argumentIndexWithClosureAsArg === null) {
            return false;
        }

        $callerWithClosureAsArgType = $callerNodeWithClosureAsArg instanceof Expr
            ? $scope->getType($callerNodeWithClosureAsArg)
            : $scope->resolveTypeByName($callerNodeWithClosureAsArg);

        foreach ($callerWithClosureAsArgType->getObjectClassReflections() as $callerWithClosureAsArgClassReflection) {
            foreach ($this->callablesAllowingCheckedExceptions as $immediateCallerAndMethod => $indexes) {
                [$callerClass, $methodName] = explode('::', $immediateCallerAndMethod);

                if (
                    $methodName === $methodNameWithClosureAsArg
                    && in_array($argumentIndexWithClosureAsArg, $indexes, true)
                    && $callerWithClosureAsArgClassReflection->is($callerClass)
                ) {
                    return true;
                }
            }
        }

        return false;
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
        array $callables,
        string $configName
    ): void
    {
        foreach ($callables as $call => $args) {
            if (strpos($call, '::') === false) {
                continue;
            }

            [$className] = explode('::', $call);

            if (!$reflectionProvider->hasClass($className)) {
                throw new LogicException("Class $className used '$configName' in does not exist.");
            }
        }
    }

}
