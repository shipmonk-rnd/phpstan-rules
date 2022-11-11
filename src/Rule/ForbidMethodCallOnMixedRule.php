<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\MixedType;
use function sprintf;

/**
 * @implements Rule<MethodCall>
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
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @return string[] errors
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->checkExplicitMixed) {
            return []; // already checked by native PHPStan
        }

        $caller = $node->var;
        $callerType = $scope->getType($caller);

        if ($callerType instanceof MixedType) {
            $name = $node->name;
            $method = $name instanceof Identifier ? $this->printer->prettyPrint([$name]) : $this->printer->prettyPrintExpr($name);

            return [
                sprintf(
                    'Method call ->%s() is prohibited on unknown type (%s)',
                    $method,
                    $this->printer->prettyPrintExpr($caller),
                ),
            ];
        }

        return [];
    }

}
