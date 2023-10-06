<?php declare(strict_types = 1);

namespace AllowNamedArgumentOnlyInAttributesRule;

use Attribute;

#[Attribute(flags: Attribute::TARGET_ALL)]
class UniversalAttribute
{
    public function __construct(int $argument)
    {
    }
}

#[UniversalAttribute(argument: 1)]
class NamedArgumentRuleExampleClass
{
    #[UniversalAttribute(argument: 1)]
    public string $foo;

    #[UniversalAttribute(argument: 1)]
    private const MY_CONST = 'const';

    #[UniversalAttribute(argument: 1)]
    private string $myProperty;

    #[UniversalAttribute(argument: 1)]
    public function myMethod(#[UniversalAttribute(argument: 5)] int $arg): void
    {
        $this->myMethod(arg: 1); // error: Named arguments are allowed only within native attributes
        var_dump(value: 1); // error: Named arguments are allowed only within native attributes
        self::myStaticMethod(arg: 1); // error: Named arguments are allowed only within native attributes
    }

    public static function myStaticMethod(int $arg): void
    {

    }

}
