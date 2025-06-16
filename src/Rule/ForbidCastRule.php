<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\Cast\Array_;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\Object_;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\Cast\Unset_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function get_class;
use function in_array;

/**
 * @implements Rule<Cast>
 */
class ForbidCastRule implements Rule
{

    private const DEFAULT_BLACKLIST = ['(array)', '(object)', '(unset)'];

    /**
     * @var string[]
     */
    private array $blacklist;

    /**
     * @param string[] $blacklist
     */
    public function __construct(array $blacklist = self::DEFAULT_BLACKLIST)
    {
        $this->blacklist = $blacklist;
    }

    public function getNodeType(): string
    {
        return Cast::class;
    }

    /**
     * @param Cast $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $castString = $this->getCastString($node);

        if (in_array($castString, $this->blacklist, true)) {
            $error = RuleErrorBuilder::message("Using $castString is discouraged, please avoid using that.")
                ->identifier('shipmonk.forbiddenCast')
                ->build();
            return [$error];
        }

        return [];
    }

    private function getCastString(Cast $node): string
    {
        if ($node instanceof Array_) {
            return '(array)';
        }

        if ($node instanceof Bool_) {
            return '(bool)';
        }

        if ($node instanceof Double) {
            return '(float)';
        }

        if ($node instanceof Int_) {
            return '(int)';
        }

        if ($node instanceof Object_) {
            return '(object)';
        }

        if ($node instanceof String_) {
            return '(string)';
        }

        if ($node instanceof Unset_) {
            return '(unset)';
        }

        throw new LogicException('Unexpected Cast child: ' . get_class($node));
    }

}
