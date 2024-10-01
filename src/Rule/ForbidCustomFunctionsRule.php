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
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use function array_map;
use function array_merge;
use function count;
use function explode;
use function gettype;
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
     * @param array<mixed, mixed> $forbiddenFunctions
     */
    public function __construct(array $forbiddenFunctions, ReflectionProvider $reflectionProvider)
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
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof MethodCall) {
            $methodNames = $this->getMethodNames($node->name, $scope);

            return $this->validateCallOverExpr($methodNames, $node->var, $scope);
        }

        if ($node instanceof StaticCall) {
            $methodNames = $this->getMethodNames($node->name, $scope);

            $classNode = $node->class;

            if ($classNode instanceof Name) {
                return $this->validateMethod($methodNames, $scope->resolveName($classNode));
            }

            return $this->validateCallOverExpr($methodNames, $classNode, $scope);
        }

        if ($node instanceof FuncCall) {
            $methodNames = $this->getFunctionNames($node->name, $scope);
            return $this->validateFunction($methodNames);
        }

        if ($node instanceof New_) {
            $classNode = $node->class;

            if ($classNode instanceof Name) {
                return $this->validateMethod(['__construct'], $scope->resolveName($classNode));
            }

            if ($classNode instanceof Expr) {
                return $this->validateConstructorWithDynamicString($classNode, $scope);
            }

            return [];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateConstructorWithDynamicString(Expr $expr, Scope $scope): array
    {
        $type = $scope->getType($expr);

        $errors = [];

        foreach ($type->getConstantStrings() as $constantStringType) {
            $errors = array_merge($errors, $this->validateMethod(['__construct'], $constantStringType->getValue()));
        }

        return $errors;
    }

    /**
     * @param list<string> $methodNames
     * @return list<IdentifierRuleError>
     */
    private function validateCallOverExpr(array $methodNames, Expr $expr, Scope $scope): array
    {
        $classType = $scope->getType($expr);
        $classNames = $classType->getObjectTypeOrClassStringObjectType()->getObjectClassNames();
        $errors = [];

        foreach ($classNames as $className) {
            $errors = [
                ...$errors,
                ...$this->validateMethod($methodNames, $className),
            ];
        }

        return $errors;
    }

    /**
     * @param list<string> $methodNames
     * @return list<IdentifierRuleError>
     */
    private function validateMethod(array $methodNames, string $className): array
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

            foreach ($methodNames as $methodName) {
                if (isset($this->forbiddenFunctions[$ancestorClassName][$methodName])) {
                    $errorMessage = sprintf('Method %s::%s() is forbidden. %s', $ancestorClassName, $methodName, $this->forbiddenFunctions[$ancestorClassName][$methodName]);
                    $errors[] = RuleErrorBuilder::message($errorMessage)
                        ->identifier('shipmonk.methodCallDenied')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @param list<string> $functionNames
     * @return list<IdentifierRuleError>
     */
    private function validateFunction(array $functionNames): array
    {
        $errors = [];

        foreach ($functionNames as $functionName) {
            if (isset($this->forbiddenFunctions[self::FUNCTION][$functionName])) {
                $errorMessage = sprintf('Function %s() is forbidden. %s', $functionName, $this->forbiddenFunctions[self::FUNCTION][$functionName]);
                $errors[] = RuleErrorBuilder::message($errorMessage)
                    ->identifier('shipmonk.functionCallDenied')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param Name|Expr $name
     * @return list<string>
     */
    private function getFunctionNames(Node $name, Scope $scope): array
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
    private function getMethodNames(Node $name, Scope $scope): array
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

}
