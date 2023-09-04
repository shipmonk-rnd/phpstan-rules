<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\MixedType;
use function get_class;
use function sprintf;

/**
 * @implements Rule<CallLike>
 */
class ForbidMethodCallOnMixedRule implements Rule
{

    private Standard $printer;

    private bool $checkExplicitMixed;

    public function __construct(Standard $printer, bool $checkExplicitMixed)
    {
        $this->printer = $printer;
        $this->checkExplicitMixed = $checkExplicitMixed;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @return list<string> errors
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->checkExplicitMixed) {
            return []; // already checked by native PHPStan
        }

        // NullsafeMethodCall not present due to https://github.com/phpstan/phpstan/issues/9830
        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            return $this->checkCall($node, $scope);
        }

        return [];
    }

    /**
     * @param MethodCall|StaticCall $node
     * @return list<string>
     */
    private function checkCall(CallLike $node, Scope $scope): array
    {
        $caller = $node instanceof StaticCall ? $node->class : $node->var;

        if (!$caller instanceof Expr) {
            return [];
        }

        $callerType = $scope->getType($caller);

        if ($callerType instanceof MixedType) {
            $name = $node->name;
            $method = $name instanceof Identifier ? $this->printer->prettyPrint([$name]) : $this->printer->prettyPrintExpr($name);

            return [
                sprintf(
                    'Method call %s%s() is prohibited on unknown type (%s)',
                    $this->getCallToken($node),
                    $method,
                    $this->printer->prettyPrintExpr($caller),
                ),
            ];
        }

        if ($callerType->isClassStringType()->yes() && $callerType->getClassStringObjectType()->getObjectClassNames() === []) {
            $name = $node->name;
            $method = $name instanceof Identifier ? $name->name : $this->printer->prettyPrintExpr($name);
            return [
                sprintf(
                    'Static call %s%s() is prohibited on class-string without its generic type.',
                    $this->getCallToken($node),
                    $method,
                ),
            ];
        }

        return [];
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    private function getCallToken(CallLike $node): string
    {
        switch (get_class($node)) {
            case StaticCall::class:
                return '::';

            case MethodCall::class:
                return '->';

            default:
                throw new LogicException('Unexpected node given: ' . get_class($node));
        }
    }

}
