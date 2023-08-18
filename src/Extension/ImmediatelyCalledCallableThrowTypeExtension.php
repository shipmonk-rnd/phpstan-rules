<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Extension;

use LogicException;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_keys;
use function in_array;
use function is_int;

class ImmediatelyCalledCallableThrowTypeExtension implements DynamicFunctionThrowTypeExtension, DynamicMethodThrowTypeExtension, DynamicStaticMethodThrowTypeExtension
{

    /**
     * class::method => callable argument index(es)
     * or
     * function => callable argument index(es)
     *
     * @var array<string, int|list<int>>
     */
    private array $immediatelyCalledCallables;

    private NodeScopeResolver $nodeScopeResolver;

    private ReflectionProvider $reflectionProvider;

    /**
     * @param array<string, int|list<int>> $immediatelyCalledCallables
     */
    public function __construct(
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        array $immediatelyCalledCallables
    )
    {
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->immediatelyCalledCallables = $immediatelyCalledCallables;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $this->isCallSupported($functionReflection);
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isCallSupported($methodReflection);
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isCallSupported($methodReflection);
    }

    /**
     * @param FunctionReflection|MethodReflection $callReflection
     */
    private function isCallSupported(object $callReflection): bool
    {
        return in_array(
            $this->getCallNotation($callReflection),
            array_keys($this->immediatelyCalledCallables),
            true,
        );
    }

    public function getThrowTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): ?Type
    {
        return $this->combineCallbackAndCallThrowTypes($functionCall, $functionReflection, $scope);
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type
    {
        return $this->combineCallbackAndCallThrowTypes($methodCall, $methodReflection, $scope);
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): ?Type
    {
        return $this->combineCallbackAndCallThrowTypes($methodCall, $methodReflection, $scope);
    }

    /**
     * @param FunctionReflection|MethodReflection $callReflection
     */
    private function combineCallbackAndCallThrowTypes(
        CallLike $call,
        object $callReflection,
        Scope $scope
    ): ?Type
    {
        if (!$scope instanceof MutatingScope) { // @phpstan-ignore-line ignore bc promise
            throw new LogicException('Unexpected scope implementation');
        }

        $argumentPositions = $this->getClosureArgumentPositions($callReflection);

        $throwTypes = $callReflection->getThrowType() !== null
            ? [$callReflection->getThrowType()]
            : [];

        foreach ($argumentPositions as $argumentPosition) {
            $args = $call->getArgs();

            if (!isset($args[$argumentPosition])) {
                continue;
            }

            $argumentValue = $args[$argumentPosition]->value;

            if ($argumentValue instanceof Closure) {
                $result = $this->nodeScopeResolver->processStmtNodes(
                    $call,
                    $argumentValue->getStmts(),
                    $scope->enterAnonymousFunction($argumentValue),
                    static function (): void {
                    },
                );

                foreach ($result->getThrowPoints() as $throwPoint) {
                    $throwTypes[] = $throwPoint->getType();
                }
            }

            if ($argumentValue instanceof StaticCall
                && $argumentValue->isFirstClassCallable()
                && $argumentValue->name instanceof Identifier
                && $argumentValue->class instanceof Name
            ) {
                $methodName = (string) $argumentValue->name;
                $className = $scope->resolveName($argumentValue->class);

                $caller = $this->reflectionProvider->getClass($className);
                $method = $caller->getMethod($methodName, $scope);

                if ($method->getThrowType() !== null) {
                    $throwTypes[] = $method->getThrowType();
                }
            }

            if ($argumentValue instanceof MethodCall
                && $argumentValue->isFirstClassCallable()
                && $argumentValue->name instanceof Identifier
            ) {
                $methodName = (string) $argumentValue->name;
                $callerType = $scope->getType($argumentValue->var);

                foreach ($callerType->getObjectClassReflections() as $callerReflection) {
                    $method = $callerReflection->getMethod($methodName, $scope);

                    if ($method->getThrowType() !== null) {
                        $throwTypes[] = $method->getThrowType();
                    }
                }
            }
        }

        if ($throwTypes === []) {
            return null;
        }

        return TypeCombinator::union(...$throwTypes);
    }

    /**
     * @param FunctionReflection|MethodReflection $callReflection
     * @return list<int>
     */
    private function getClosureArgumentPositions(object $callReflection): array
    {
        $arguments = $this->immediatelyCalledCallables[$this->getCallNotation($callReflection)];

        if (is_int($arguments)) {
            return [$arguments];
        }

        return $arguments;
    }

    /**
     * @param FunctionReflection|MethodReflection $callReflection
     */
    private function getCallNotation(object $callReflection): string
    {
        if ($callReflection instanceof MethodReflection) {
            $class = $callReflection->getDeclaringClass()->getName();
            $method = $callReflection->getName();

            return "{$class}::{$method}";
        }

        return $callReflection->getName();
    }

}
