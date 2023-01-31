<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\TypeUtils;
use function count;
use function explode;
use function is_string;
use function sprintf;

/**
 * @implements Rule<CallLike>
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
     * @param array<string, mixed> $forbiddenFunctions
     */
    public function __construct(array $forbiddenFunctions, ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;

        foreach ($forbiddenFunctions as $forbiddenFunction => $description) {
            if (!is_string($description)) {
                throw new LogicException('Unexpected forbidden function description, string expected');
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

            $this->forbiddenFunctions[$className][$methodName] = $description;
        }
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof MethodCall) {
            $methodName = $this->getMethodName($node->name, $scope);

            if ($methodName === null) {
                return [];
            }

            return $this->validateCallOverExpr($methodName, $node->var, $scope);
        }

        if ($node instanceof StaticCall) {
            $methodName = $this->getMethodName($node->name, $scope);

            if ($methodName === null) {
                return [];
            }

            $classNode = $node->class;

            if ($classNode instanceof Name) {
                return $this->validateMethod($methodName, $scope->resolveName($classNode));
            }

            return $this->validateCallOverExpr($methodName, $classNode, $scope);
        }

        if ($node instanceof FuncCall) {
            $methodName = $this->getFunctionName($node->name, $scope);

            if ($methodName === null) {
                return [];
            }

            return $this->validateFunction($methodName);
        }

        if ($node instanceof New_) {
            $classNode = $node->class;

            if ($classNode instanceof Name) {
                return $this->validateMethod('__construct', $scope->resolveName($classNode));
            }

            if ($classNode instanceof Expr) {
                return $this->validateConstructorWithDynamicString($classNode, $scope);
            }

            return [];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateConstructorWithDynamicString(Expr $expr, Scope $scope): array
    {
        $type = $scope->getType($expr);

        if ($type instanceof ConstantStringType) {
            return $this->validateMethod('__construct', $type->getValue());
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateCallOverExpr(string $methodName, Expr $expr, Scope $scope): array
    {
        $classType = $scope->getType($expr);

        if ($classType instanceof GenericClassStringType) {
            $classType = $classType->getGenericType();
        }

        $classNames = TypeUtils::getDirectClassNames($classType);
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
     * @return list<string>
     */
    private function validateMethod(string $methodName, string $className): array
    {
        foreach ($this->reflectionProvider->getClass($className)->getAncestors() as $ancestor) {
            $ancestorClassName = $ancestor->getName();

            if (isset($this->forbiddenFunctions[$ancestorClassName][self::ANY_METHOD])) {
                return [sprintf('Class %s is forbidden. %s', $ancestorClassName, $this->forbiddenFunctions[$ancestorClassName][self::ANY_METHOD])];
            }

            if (isset($this->forbiddenFunctions[$ancestorClassName][$methodName])) {
                return [sprintf('Method %s::%s() is forbidden. %s', $ancestorClassName, $methodName, $this->forbiddenFunctions[$ancestorClassName][$methodName])];
            }
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateFunction(string $functionName): array
    {
        if (isset($this->forbiddenFunctions[self::FUNCTION][$functionName])) {
            return [sprintf('Function %s() is forbidden. %s', $functionName, $this->forbiddenFunctions[self::FUNCTION][$functionName])];
        }

        return [];
    }

    /**
     * @param Name|Expr $name
     */
    private function getFunctionName(Node $name, Scope $scope): ?string
    {
        if ($name instanceof Name) {
            return $this->reflectionProvider->resolveFunctionName($name, $scope);
        }

        $nameType = $scope->getType($name);

        if ($nameType instanceof ConstantStringType) {
            return $nameType->getValue();
        }

        return null;
    }

    /**
     * @param Name|Expr|Identifier $name
     */
    private function getMethodName(Node $name, Scope $scope): ?string
    {
        if ($name instanceof Name) {
            return $name->toString();
        }

        if ($name instanceof Identifier) {
            return $name->toString();
        }

        $nameType = $scope->getType($name);

        if ($nameType instanceof ConstantStringType) {
            return $nameType->getValue();
        }

        return null;
    }

}
