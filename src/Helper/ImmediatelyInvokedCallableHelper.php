<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Helper;

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
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use function array_values;
use function spl_object_hash;

class ImmediatelyInvokedCallableHelper
{

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * Returns spl_object_hashes of callable argument nodes that are
     * param-immediately-invoked-callable or directly invoked.
     *
     * @return array<string, true>
     */
    public function getImmediatelyInvokedHashes(
        CallLike $node,
        Scope $scope
    ): array
    {
        // Directly invoked callable syntax: (function(){...})(), (fn() => ...)()
        if ($node instanceof FuncCall && $this->isCallableExpression($node->name)) {
            return [spl_object_hash($node->name) => true];
        }

        $analysis = $this->analyzeCall($node, $scope);

        return $analysis !== null ? $analysis->immediatelyInvokedHashes : [];
    }

    /**
     * Full analysis of a call — returns the resolved reflection, caller type,
     * reordered arguments, and which argument hashes are immediately invoked.
     *
     * Returns null for calls that cannot be resolved (e.g. dynamic method names).
     */
    public function analyzeCall(
        CallLike $node,
        Scope $scope
    ): ?CallAnalysisResult
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

        } else {
            return null;
        }

        if ($methodReflection === null) {
            return null;
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

        } else {
            $arguments = (ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $node) ?? $node)->getArgs();
        }

        /** @var list<Arg> $args */
        $args = array_values($arguments);
        $parameters = $parametersAcceptor->getParameters();

        $immediatelyInvokedHashes = [];

        foreach ($args as $index => $arg) {
            $parameterIndex = $this->getParameterIndex($arg, $index, $parameters) ?? -1;
            $parameter = $parameters[$parameterIndex] ?? null;

            if ($this->isImmediatelyInvokedCallable($methodReflection, $parameter)) {
                $immediatelyInvokedHashes[spl_object_hash($arg->value)] = true;
            }
        }

        return new CallAnalysisResult(
            $methodReflection,
            $callerType,
            $args,
            $immediatelyInvokedHashes,
        );
    }

    public function isCallableExpression(Node $node): bool
    {
        return $node instanceof Closure
            || $node instanceof ArrowFunction
            || ($node instanceof MethodCall && $node->isFirstClassCallable())
            || ($node instanceof NullsafeMethodCall && $node->isFirstClassCallable())
            || ($node instanceof StaticCall && $node->isFirstClassCallable())
            || ($node instanceof FuncCall && $node->isFirstClassCallable());
    }

    /**
     * Copied from phpstan
     *
     * @param FunctionReflection|MethodReflection $reflection
     *
     * @see https://github.com/phpstan/phpstan-src/commit/cefa296f24b8c0b7d4dc3d383cbceea35267cb3f
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
