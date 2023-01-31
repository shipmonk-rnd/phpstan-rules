<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Return_>
 */
class ForbidReturnInConstructorRule implements Rule
{

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * @param Return_ $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($scope->isInAnonymousFunction()) {
            return [];
        }

        $methodReflection = $scope->getFunction();

        if (!$methodReflection instanceof MethodReflection) {
            return [];
        }

        if ($methodReflection->getName() === '__construct') {
            // needed mainly for UselessPrivatePropertyDefaultValueRule as it expects all top-level calls in constructors are always executed
            return ['Using return statement in constructor is forbidden to be able to check useless default values. Either create static constructors of use if-else.'];
        }

        return [];
    }

}
