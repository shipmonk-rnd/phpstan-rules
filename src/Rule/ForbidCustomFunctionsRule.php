<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function explode;
use function gettype;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class ForbidCustomFunctionsRule implements Rule
{

    private const ANY_METHOD = '*';
    private const FUNCTION = '';

    /**
     * @var array<string, array<string, string>>
     */
    private array $forbiddenFunctions = [];

    private ReflectionProvider $reflectionProvider;

    /**
     * @param array<mixed, mixed> $forbiddenFunctions
     */
    public function __construct(
        array $forbiddenFunctions,
        ReflectionProvider $reflectionProvider
    )
    {
        $this->reflectionProvider = $reflectionProvider;

        foreach ($forbiddenFunctions as $forbiddenFunction => $description) {
            if (!is_string($forbiddenFunction)) {
                throw new LogicException("Unexpected forbidden function name, string expected, got $forbiddenFunction. Usage: ['var_dump' => 'Remove debug code!'].");
            }

            if (!is_string($description)) {
                throw new LogicException('Unexpected forbidden function description, string expected, got ' . gettype($description) . '. Usage: [\'var_dump\' => \'Remove debug code!\'].');
            }

            $parts = explode('::', $forbiddenFunction);

            if (count($parts) === 1) {
                $className = self::FUNCTION;
                $methodName = $parts[0];
            } elseif (count($parts) === 2) {
                $className = $parts[0];
                $methodName = $parts[1];
            } else {
                throw new LogicException("Unexpected format of forbidden function {$forbiddenFunction}, expected Namespace\Class::methodName");
            }

            if ($className !== self::FUNCTION && !$reflectionProvider->hasClass($className)) {
                throw new LogicException("Class {$className} used in 'forbiddenFunctions' does not exist");
            }

            $this->forbiddenFunctions[$className][$methodName] = $description;
        }
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
        if ($this->isFirstClassCallableNode($node)) {
            $node = $node->getOriginalNode(); // @phpstan-ignore shipmonk.variableTypeOverwritten
        }

        if ($node instanceof FuncCall) {
            return $this->validateFunctionCall($node, $scope);
        }

        if ($node instanceof MethodCall) {
            $caller = $scope->getType($node->var);
            $methodNames = $this->getMethodNames($node->name, $scope);

        } elseif ($node instanceof StaticCall) {
            $classNode = $node->class;
            $caller = $classNode instanceof Name ? $scope->resolveTypeByName($classNode) : $scope->getType($classNode);
            $methodNames = $this->getMethodNames($node->name, $scope);

        } elseif ($node instanceof New_) {
            $caller = $this->getNewCaller($node, $scope);
            $methodNames = ['__construct'];

        } else {
            return [];
        }

        $errors = [];

        foreach ($methodNames as $methodName) {
            $errors = [
                ...$errors,
                ...$this->validateCallOverExpr($methodName, $caller),
                ...$this->validateCallLikeArguments($caller, $methodName, $node, $scope),
            ];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateFunctionCall(
        FuncCall $node,
        Scope $scope
    ): array
    {
        $functionNames = $this->getFunctionNames($node->name, $scope);

        $errors = [];

        foreach ($functionNames as $functionName) {
            $errors = [
                ...$errors,
                ...$this->validateFunction($functionName),
                ...$this->validateFunctionArguments($functionName, $node, $scope),
            ];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateCallOverExpr(
        string $methodName,
        Type $caller
    ): array
    {
        $classNames = $caller->getObjectTypeOrClassStringObjectType()->getObjectClassNames();
        $errors = [];

        foreach ($classNames as $className) {
            $errors = [
                ...$errors,
                ...$this->validateMethod($methodName, $className),
            ];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateMethod(
        string $methodName,
        string $className
    ): array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $errors = [];

        foreach ($this->reflectionProvider->getClass($className)->getAncestors() as $ancestor) {
            $ancestorClassName = $ancestor->getName();

            if (isset($this->forbiddenFunctions[$ancestorClassName][self::ANY_METHOD])) {
                $errorMessage = sprintf('Class %s is forbidden. %s', $ancestorClassName, $this->forbiddenFunctions[$ancestorClassName][self::ANY_METHOD]);
                $errors[] = RuleErrorBuilder::message($errorMessage)
                    ->identifier('shipmonk.methodCallDenied')
                    ->build();
            }

            if (isset($this->forbiddenFunctions[$ancestorClassName][$methodName])) {
                $errorMessage = sprintf('Method %s::%s() is forbidden. %s', $ancestorClassName, $methodName, $this->forbiddenFunctions[$ancestorClassName][$methodName]);
                $errors[] = RuleErrorBuilder::message($errorMessage)
                    ->identifier('shipmonk.methodCallDenied')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateFunction(string $functionName): array
    {
        $errors = [];

        if (isset($this->forbiddenFunctions[self::FUNCTION][$functionName])) {
            $errorMessage = sprintf('Function %s() is forbidden. %s', $functionName, $this->forbiddenFunctions[self::FUNCTION][$functionName]);
            $errors[] = RuleErrorBuilder::message($errorMessage)
                ->identifier('shipmonk.functionCallDenied')
                ->build();
        }

        return $errors;
    }

    /**
     * @param Name|Expr $name
     * @return list<string>
     */
    private function getFunctionNames(
        Node $name,
        Scope $scope
    ): array
    {
        if ($name instanceof Name) {
            $functionName = $this->reflectionProvider->resolveFunctionName($name, $scope);
            return $functionName === null ? [] : [$functionName];
        }

        $nameType = $scope->getType($name);

        return array_map(
            static fn (ConstantStringType $type) => $type->getValue(),
            $nameType->getConstantStrings(),
        );
    }

    /**
     * @param Name|Expr|Identifier $name
     * @return list<string>
     */
    private function getMethodNames(
        Node $name,
        Scope $scope
    ): array
    {
        if ($name instanceof Name) {
            return [$name->toString()];
        }

        if ($name instanceof Identifier) {
            return [$name->toString()];
        }

        $nameType = $scope->getType($name);

        return array_map(
            static fn (ConstantStringType $type) => $type->getValue(),
            $nameType->getConstantStrings(),
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateCallable(
        Expr $callable,
        Scope $scope
    ): array
    {
        $callableType = $scope->getType($callable);

        if (!$callableType->isCallable()->yes()) {
            return [];
        }

        $errors = [];

        foreach ($callableType->getConstantStrings() as $constantString) {
            $errors = [
                ...$errors,
                ...$this->validateFunction($constantString->getValue()),
            ];
        }

        foreach ($callableType->getConstantArrays() as $constantArray) {
            $callableTypeAndNames = $constantArray->findTypeAndMethodNames();

            foreach ($callableTypeAndNames as $typeAndName) {
                if ($typeAndName->isUnknown()) {
                    continue;
                }

                $classNames = $typeAndName->getType()->getObjectClassNames();
                $methodName = $typeAndName->getMethod();

                foreach ($classNames as $className) {
                    $errors = [
                        ...$errors,
                        ...$this->validateMethod($methodName, $className),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateCallLikeArguments(
        Type $caller,
        string $methodName,
        CallLike $node,
        Scope $scope
    ): array
    {
        if ($node->isFirstClassCallable()) {
            return [];
        }

        $errors = [];

        foreach ($caller->getObjectTypeOrClassStringObjectType()->getObjectClassNames() as $className) {
            $methodReflection = $this->getMethodReflection($className, $methodName, $scope);

            if ($methodReflection === null) {
                continue;
            }

            $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $methodReflection->getVariants());
            $reorderedArgs = ArgumentsNormalizer::reorderArgs($parametersAcceptor, $node->getArgs()) ?? $node->getArgs();

            $errors = [
                ...$errors,
                ...$this->validateCallableArguments($reorderedArgs, $parametersAcceptor, $scope),
            ];
        }

        return $errors;
    }

    /**
     * @param array<Arg> $reorderedArgs
     * @return list<IdentifierRuleError>
     */
    private function validateCallableArguments(
        array $reorderedArgs,
        ParametersAcceptor $parametersAcceptor,
        Scope $scope
    ): array
    {
        $errors = [];

        foreach ($parametersAcceptor->getParameters() as $index => $parameter) {
            if (TypeCombinator::removeNull($parameter->getType())->isCallable()->yes() && isset($reorderedArgs[$index])) {
                $errors = [
                    ...$errors,
                    ...$this->validateCallable($reorderedArgs[$index]->value, $scope),
                ];
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateFunctionArguments(
        string $functionName,
        FuncCall $node,
        Scope $scope
    ): array
    {
        if ($node->isFirstClassCallable()) {
            return [];
        }

        $functionReflection = $this->getFunctionReflection(new Name($functionName), $scope);

        if ($functionReflection === null) {
            return [];
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $functionReflection->getVariants());
        $funcCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

        if ($funcCall === null) {
            $funcCall = $node;
        }

        $orderedArgs = $funcCall->getArgs();

        return $this->validateCallableArguments($orderedArgs, $parametersAcceptor, $scope);
    }

    private function getMethodReflection(
        string $className,
        string $methodName,
        Scope $scope
    ): ?ExtendedMethodReflection
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasMethod($methodName)) {
            return null;
        }

        return $classReflection->getMethod($methodName, $scope);
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

    private function getNewCaller(
        New_ $new,
        Scope $scope
    ): Type
    {
        if ($new->class instanceof Class_) {
            $anonymousClassReflection = $this->reflectionProvider->getAnonymousClassReflection($new->class, $scope);
            return new ObjectType($anonymousClassReflection->getName());
        }

        if ($new->class instanceof Name) {
            return $scope->resolveTypeByName($new->class);
        }

        return $scope->getType($new->class);
    }

    /**
     * @phpstan-assert-if-true FunctionCallableNode|MethodCallableNode|StaticMethodCallableNode $node
     */
    private function isFirstClassCallableNode(Node $node): bool
    {
        return $node instanceof FunctionCallableNode
            || $node instanceof MethodCallableNode
            || $node instanceof StaticMethodCallableNode;
    }

}
