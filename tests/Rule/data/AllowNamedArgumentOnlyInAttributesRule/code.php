<?php declare(strict_types = 1);

namespace AllowNamedArgumentOnlyInAttributesRule;

use Attribute;

#[Attribute(flags: Attribute::TARGET_ALL)]
class UniversalWrapperAttribute
{
    public function __construct(UniversalAttribute $attribute, NonAttributeCrate $crate)
    {
    }
}

#[Attribute(flags: Attribute::TARGET_ALL)]
class UniversalAttribute
{
    public function __construct(int $argument)
    {
    }
}

class NonAttributeCrate
{
    public function __construct(int $value)
    {
    }
}

#[UniversalAttribute(argument: 1)]
class NamedArgumentRuleExampleClass
{
    #[UniversalWrapperAttribute(attribute: new UniversalAttribute(argument: 1), crate: new NonAttributeCrate(value: 2))]
    public string $foo;

    #[UniversalWrapperAttribute(attribute: new UniversalAttribute(argument: 1), crate: new NonAttributeCrate(value: 2))]
    private const MY_CONST = 'const';

    #[UniversalWrapperAttribute(attribute: new UniversalAttribute(argument: 1), crate: new NonAttributeCrate(value: 2))]
    private string $myProperty;

    #[UniversalWrapperAttribute(attribute: new UniversalAttribute(argument: 1), crate: new NonAttributeCrate(value: 2))]
    public function myMethod(#[UniversalWrapperAttribute(attribute: new UniversalAttribute(argument: 1), crate: new NonAttributeCrate(value: 2))] int $arg): void
    {
        $this->myMethod(arg: 1); // error: Named arguments are allowed only within native attributes
        var_dump(value: 1); // error: Named arguments are allowed only within native attributes
        self::myStaticMethod(arg: 1); // error: Named arguments are allowed only within native attributes
    }

    public static function myStaticMethod(int $arg): void
    {
        $foo = new UniversalAttribute(argument: 1); // error: Named arguments are allowed only within native attributes
    }

}
