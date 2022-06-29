<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<RequirePreviousExceptionPassRule>
 */
class RequirePreviousExceptionPassRuleTest extends RuleTestCase
{

    private ?bool $ignoreIfExceptionIsNotAcceptableByRethrownOne = null;

    protected function getRule(): Rule
    {
        if ($this->ignoreIfExceptionIsNotAcceptableByRethrownOne === null) {
            throw new LogicException('Testcase need to initialize this');
        }

        return new RequirePreviousExceptionPassRule(
            self::getContainer()->getByType(Standard::class),
            $this->ignoreIfExceptionIsNotAcceptableByRethrownOne,
        );
    }

    public function testWithAcceptCheck(): void
    {
        $this->ignoreIfExceptionIsNotAcceptableByRethrownOne = true;
        $this->analyseFile(__DIR__ . '/data/RequirePreviousExceptionPassRule/with-accept-check.php');
    }

    public function testWithoutAcceptCheck(): void
    {
        $this->ignoreIfExceptionIsNotAcceptableByRethrownOne = false;
        $this->analyseFile(__DIR__ . '/data/RequirePreviousExceptionPassRule/without-accept-check.php');
    }

}
