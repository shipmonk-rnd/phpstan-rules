<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FileNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\ExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_values;
use function spl_object_hash;

/**
 * @implements Rule<Node>
 */
class ForbidCheckedExceptionInYieldingMethodRule implements Rule
{

    private ExceptionTypeResolver $exceptionTypeResolver;

    private ReflectionProvider $reflectionProvider;

    /**
     * @var array<string, true>
     */
    private array $immediatelyInvokedClosures = [];

    /**
     * @var list<ClosureReturnStatementsNode>
     */
    private array $pendingClosures = [];

    public function __construct(
        ExceptionTypeResolver $exceptionTypeResolver,
        ReflectionProvider $reflectionProvider
    )
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
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
            $this->immediatelyInvokedClosures = [];
            $this->pendingClosures = [];

        } elseif ($node instanceof CallLike) {
            $this->trackImmediatelyInvokedClosures($node, $scope);

        } elseif ($node instanceof ClosureReturnStatementsNode) {
            if ($node->getStatementResult()->hasYield()) {
                $this->pendingClosures[] = $node;
            }

        } elseif ($node instanceof ReturnStatementsNode) {
            if ($node->getStatementResult()->hasYield()) {
                $errors = $this->checkThrowPoints($node);
            }
        }

        if (!$scope->isInClass() || $node instanceof ClassMethodsNode) {
            foreach ($this->pendingClosures as $closureNode) {
                $closureHash = spl_object_hash($closureNode->getClosureExpr());

                if (isset($this->immediatelyInvokedClosures[$closureHash])) {
                    foreach ($this->checkThrowPoints($closureNode) as $error) {
                        $errors[] = $error;
                    }
                }
            }

            $this->pendingClosures = [];
        }

        return $errors;
    }

    private function trackImmediatelyInvokedClosures(
        CallLike $node,
        Scope $scope
    ): void
    {
        // Directly invoked closure: (function(){...})()
        if ($node instanceof FuncCall && $node->name instanceof Closure) {
            $this->immediatelyInvokedClosures[spl_object_hash($node->name)] = true;
            return;
        }

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
            $methodReflection = $this->getFunctionReflection($node->name, $scope);

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

        } else {
            $arguments = (ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $node) ?? $node)->getArgs();
        }

        /** @var list<Arg> $args */
        $args = array_values($arguments);
        $parameters = $parametersAcceptor->getParameters();

        foreach ($args as $index => $arg) {
            if (!$arg->value instanceof Closure) {
                continue;
            }

            $parameter = $parameters[$index] ?? null;

            if ($this->isImmediatelyInvokedCallable($methodReflection, $parameter)) {
                $this->immediatelyInvokedClosures[spl_object_hash($arg->value)] = true;
            }
        }
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkThrowPoints(ReturnStatementsNode $node): array
    {
        $errors = [];
        $functionName = $this->getFunctionName($node);

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $exceptionClass) {
                if ($this->exceptionTypeResolver->isCheckedException($exceptionClass, $throwPoint->getScope())) {
                    $errors[] = RuleErrorBuilder::message("Throwing checked exception $exceptionClass in yielding $functionName is denied as it gets thrown upon Generator iteration")
                        ->line($throwPoint->getNode()->getStartLine())
                        ->identifier('shipmonk.checkedExceptionInYieldingMethod')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
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

    private function getFunctionReflection(
        Name $functionName,
        Scope $scope
    ): ?FunctionReflection
    {
        return $this->reflectionProvider->hasFunction($functionName, $scope)
            ? $this->reflectionProvider->getFunction($functionName, $scope)
            : null;
    }

    private function getFunctionName(ReturnStatementsNode $node): string
    {
        if ($node instanceof ClosureReturnStatementsNode) {
            return 'closure';
        }

        if ($node instanceof FunctionReturnStatementsNode) {
            return 'function';
        }

        if ($node instanceof PropertyHookReturnStatementsNode) {
            return 'property hook';
        }

        return 'method';
    }

}
