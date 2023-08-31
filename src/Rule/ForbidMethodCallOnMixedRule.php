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
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeUtils;
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
     * @return list<IdentifierRuleError>
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

        $callerType = TypeUtils::toBenevolentUnion($scope->getType($caller));

        if ($callerType->getObjectTypeOrClassStringObjectType()->getObjectClassNames() === []) {
            $name = $node->name;
            $method = $name instanceof Identifier ? $this->printer->prettyPrint([$name]) : $this->printer->prettyPrintExpr($name);

            $errorMessage = sprintf(
                'Method call %s%s() is prohibited on unknown type (%s)',
                $this->getCallToken($node),
                $method,
                $this->printer->prettyPrintExpr($caller),
            );
            $error = RuleErrorBuilder::message($errorMessage)
                ->identifier('methodCallOnMixed')
                ->build();
            return [$error];
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
